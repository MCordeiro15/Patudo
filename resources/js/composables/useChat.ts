// Composable do chat (TalkJS). Liga ao TalkJS com os dados do Laravel e expõe a lista de conversas e a ação de
// abrir conversa. As mensagens (enviar, editar, apagar, reagir) são tratadas pela caixa <t-chatbox>.
// Chat composable (TalkJS). Connects to TalkJS using the data from Laravel and exposes the conversation list
// and the open-conversation action. Messages (send, edit, delete, react) are handled by the <t-chatbox> box.
import { getTalkSession } from '@talkjs/core';
import type { ConversationSnapshot, TalkSession } from '@talkjs/core';
import { computed, onBeforeUnmount, ref, shallowRef } from 'vue';

export interface ChatPerson {
    id: string;
    name: string;
    role: string;
    // URL da foto de perfil (vem do Laravel ou do TalkJS); null se não tiver
    photo_url?: string | null;
}

export interface ChatListItem extends ChatPerson {
    lastText: string;
    lastAt: number | null;
    unread: number;
}

interface SessionInfo {
    app_id: string;
    user: ChatPerson;
    token: string | null;
}

interface Unsubscribable {
    unsubscribe: () => void;
}

async function getJson<T>(url: string): Promise<T> {
    const response = await fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error(`O pedido a ${url} falhou (${response.status}).`);
    }

    return (await response.json()) as T;
}

export function useChat() {
    const me = ref<ChatPerson | null>(null);
    const contacts = ref<ChatPerson[]>([]);
    const conversations = shallowRef<ConversationSnapshot[]>([]);
    const knownPeople = ref<Record<string, ChatPerson>>({});
    const activeOtherId = ref<string | null>(null);
    const activeOnline = ref(false);
    const ready = ref(false);
    const error = ref<string | null>(null);
    const appId = ref<string | null>(null);
    // Só existe quando a autenticação do TalkJS está ligada; a caixa usa-o para pedir um token novo.
    // Only set when TalkJS authentication is on; the chatbox uses it to ask for a fresh token.
    const tokenFetcher = ref<(() => Promise<string>) | undefined>(undefined);

    let session: TalkSession | null = null;
    let conversationsSub: Unsubscribable | null = null;
    let onlineSub: Unsubscribable | null = null;
    const userSubs = new Map<string, Unsubscribable>();

    // ID único por par de utilizadores, igual para os dois lados (ex.: chat_3_12).
    // Unique ID per pair of users, the same on both sides (e.g. chat_3_12).
    function conversationIdFor(otherId: string): string {
        const ids = [Number(me.value?.id), Number(otherId)].sort((a, b) => a - b);

        return `chat_${ids[0]}_${ids[1]}`;
    }

    function otherIdFrom(conversationId: string): string | null {
        const match = /^chat_(\d+)_(\d+)$/.exec(conversationId);

        if (!match || !me.value) {
            return null;
        }

        if (match[1] === me.value.id) {
            return match[2];
        }

        return match[2] === me.value.id ? match[1] : null;
    }

    // Vai buscar ao TalkJS o nome de quem escreveu (útil para o petsitter, que não tem lista de contactos).
    // Fetches the name of the other person from TalkJS (useful for petsitters, who have no contact list).
    function watchPerson(otherId: string): void {
        if (!session || userSubs.has(otherId)) {
            return;
        }

        userSubs.set(
            otherId,
            session.user(otherId).subscribe((snapshot) => {
                if (!snapshot) {
                    return;
                }

                knownPeople.value = {
                    ...knownPeople.value,
                    [otherId]: {
                        id: otherId,
                        name: snapshot.name,
                        role: snapshot.custom['papel'] || 'Dono',
                        photo_url: snapshot.photoUrl,
                    },
                };
            }),
        );
    }

    async function refreshToken(): Promise<void> {
        try {
            const info = await getJson<SessionInfo>('/api/chat/session');

            if (info.token) {
                session?.setToken(info.token);
            }
        } catch (e) {
            console.error('Erro ao renovar o token do chat:', e);
        }
    }

    async function start(): Promise<void> {
        try {
            const info = await getJson<SessionInfo>('/api/chat/session');
            const list = await getJson<{ data: ChatPerson[] }>('/api/chat/contacts');

            me.value = info.user;
            contacts.value = list.data;
            appId.value = info.app_id;

            if (info.token) {
                tokenFetcher.value = async () => {
                    const fresh = await getJson<SessionInfo>('/api/chat/session');

                    return fresh.token ?? '';
                };
            }

            session = getTalkSession({
                appId: info.app_id,
                userId: info.user.id,
                token: info.token ?? undefined,
            });

            // Só há renovação de token quando a autenticação do TalkJS está ligada.
            // Token renewal only applies when TalkJS authentication is enabled.
            if (info.token) {
                session.onNeedToken = () => {
                    void refreshToken();
                };
            }

            await session.currentUser.createIfNotExists({
                name: info.user.name,
                custom: { papel: info.user.role },
                photoUrl: info.user.photo_url ?? undefined,
            });

            // createIfNotExists não altera quem já existe: atualiza o nome e a foto em cada entrada no chat
            // createIfNotExists does not update existing users: refresh name and photo on every chat start
            await session.currentUser.set({
                name: info.user.name,
                photoUrl: info.user.photo_url ?? null,
                // textos do TalkJS em português ("Hoje", "Ontem"...); o TalkJS só tem pt-BR
                locale: 'pt-BR',
            });

            conversationsSub = session.subscribeConversations((snapshots) => {
                conversations.value = snapshots ?? [];

                (snapshots ?? []).forEach((conversation) => {
                    const otherId = otherIdFrom(conversation.id);

                    if (otherId) {
                        watchPerson(otherId);
                    }
                });
            });

            ready.value = true;
        } catch (e) {
            error.value = 'Não foi possível iniciar o chat.';
            console.error(e);
        }
    }

    // Lista da esquerda: contactos (donos) + conversas existentes, da mais recente para a mais antiga.
    // Left list: contacts (owners) + existing conversations, most recent first.
    const items = computed<ChatListItem[]>(() => {
        const myId = me.value?.id;
        const byOther = new Map<string, ChatListItem>();

        contacts.value.forEach((contact) => {
            byOther.set(contact.id, { ...contact, lastText: '', lastAt: null, unread: 0 });
        });

        conversations.value.forEach((conversation) => {
            const otherId = otherIdFrom(conversation.id);
            const last = conversation.lastMessage;

            // Conversas vazias de quem não é contacto não aparecem (o dono abriu mas ainda não escreveu).
            // Empty conversations from non-contacts are hidden (the owner opened it but has not written yet).
            if (!otherId || (!last && !byOther.has(otherId))) {
                return;
            }

            const person: ChatPerson = byOther.get(otherId) ??
                knownPeople.value[otherId] ?? { id: otherId, name: `Utilizador ${otherId}`, role: 'Dono' };

            byOther.set(otherId, {
                id: person.id,
                name: person.name,
                role: person.role,
                photo_url: person.photo_url ?? null,
                lastText: last ? (last.sender?.id === myId ? 'Tu: ' : '') + last.plaintext : '',
                lastAt: last ? last.createdAt : null,
                unread: otherId === activeOtherId.value ? 0 : conversation.unreadMessageCount,
            });
        });

        // Só aparecem conversas com mensagens (e a que está aberta, mesmo vazia). Os contactos sem mensagens
        // não aparecem: uma conversa nova começa no perfil do petsitter (/messages?with=ID).
        // Only conversations with messages are shown (plus the open one, even if empty).
        return [...byOther.values()]
            .filter((item) => item.lastAt !== null || item.id === activeOtherId.value)
            .sort((a, b) => {
            if (a.lastAt !== null && b.lastAt !== null) {
                return b.lastAt - a.lastAt;
            }

            if (a.lastAt !== null) {
                return -1;
            }

            if (b.lastAt !== null) {
                return 1;
            }

            return a.name.localeCompare(b.name, 'pt');
        });
    });

    const totalUnread = computed<number>(() => items.value.reduce((sum, item) => sum + item.unread, 0));

    const activePerson = computed<ChatListItem | null>(
        () => items.value.find((item) => item.id === activeOtherId.value) ?? null,
    );

    async function openConversation(otherId: string): Promise<void> {
        if (!session || !me.value) {
            return;
        }

        try {
            error.value = null;
            const conversation = session.conversation(conversationIdFor(otherId));
            const contact = contacts.value.find((c) => c.id === otherId);

            // Só quem tem o outro na lista de contactos (o dono) pode iniciar a conversa.
            // Only someone who has the other person as a contact (the owner) can start the conversation.
            if (contact) {
                await session.user(otherId).createIfNotExists({
                    name: contact.name,
                    custom: { papel: contact.role },
                    photoUrl: contact.photo_url ?? undefined,
                });

                // Atualiza a foto de quem já existia no TalkJS sem ela. Se o TalkJS não deixar (autenticação
                // ligada), ignora: a foto atualiza-se quando essa pessoa abrir o chat.
                // Updates the photo of users who already existed without it; ignored if TalkJS does not allow it.
                try {
                    await session.user(otherId).set({ photoUrl: contact.photo_url ?? null });
                } catch {
                    // sem permissão para alterar outro utilizador
                }
                await conversation.createIfNotExists();
                await conversation.participant(otherId).createIfNotExists();
            }

            activeOtherId.value = otherId;
            activeOnline.value = false;

            onlineSub?.unsubscribe();
            onlineSub = session.user(otherId).subscribeOnline((snapshot) => {
                activeOnline.value = snapshot?.isConnected ?? false;
            });
        } catch (e) {
            error.value = 'Não foi possível abrir a conversa.';
            console.error(e);
        }
    }

    // Pode abrir conversa com esta pessoa? Sim se for um contacto (dono -> petsitter) ou se já houver conversa.
    // Can a conversation with this person be opened? Yes if they are a contact or a conversation already exists.
    function canOpen(otherId: string): boolean {
        return (
            contacts.value.some((contact) => contact.id === otherId) ||
            conversations.value.some((conversation) => otherIdFrom(conversation.id) === otherId)
        );
    }

    // ID da conversa aberta, para a caixa <t-chatbox>.
    // ID of the open conversation, for the <t-chatbox> box.
    const activeConversationId = computed<string | null>(() =>
        activeOtherId.value ? conversationIdFor(activeOtherId.value) : null,
    );

    onBeforeUnmount(() => {
        conversationsSub?.unsubscribe();
        onlineSub?.unsubscribe();
        userSubs.forEach((subscription) => subscription.unsubscribe());
        userSubs.clear();
    });

    return {
        me,
        appId,
        items,
        activeOtherId,
        activeConversationId,
        activePerson,
        activeOnline,
        tokenFetcher,
        totalUnread,
        ready,
        error,
        start,
        openConversation,
        canOpen,
    };
}