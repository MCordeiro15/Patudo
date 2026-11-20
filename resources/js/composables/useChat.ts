// Composable do chat (TalkJS). Liga ao TalkJS com os dados do Laravel e expõe a lista de conversas, as mensagens
// e as ações de abrir conversa e enviar mensagem. As mensagens não passam pelo Laravel.
// Chat composable (TalkJS). Connects to TalkJS using the data from Laravel and exposes the conversation list,
// the messages and the open-conversation and send-message actions. Messages do not go through Laravel.
import { getTalkSession } from '@talkjs/core';
import type { ConversationSnapshot, MessageSnapshot, TalkSession } from '@talkjs/core';
import { computed, onBeforeUnmount, ref, shallowRef } from 'vue';

// Reações disponíveis. Cada pessoa pode dar cada reação uma vez por mensagem.
// Available reactions. Each person can give each reaction once per message.
export const REACTION_EMOJIS = ['👍', '❤️', '😂', '😮', '🙏'];

export interface ChatPerson {
    id: string;
    name: string;
    role: string;
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
    const messages = shallowRef<MessageSnapshot[]>([]);
    const activeOtherId = ref<string | null>(null);
    const activeOnline = ref(false);
    const ready = ref(false);
    const error = ref<string | null>(null);

    let session: TalkSession | null = null;
    let conversationsSub: Unsubscribable | null = null;
    let messagesSub: Unsubscribable | null = null;
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
                lastText: last ? (last.sender?.id === myId ? 'Tu: ' : '') + last.plaintext : '',
                lastAt: last ? last.createdAt : null,
                unread: otherId === activeOtherId.value ? 0 : conversation.unreadMessageCount,
            });
        });

        return [...byOther.values()].sort((a, b) => {
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
                });
                await conversation.createIfNotExists();
                await conversation.participant(otherId).createIfNotExists();
            }

            activeOtherId.value = otherId;
            messages.value = [];
            activeOnline.value = false;

            messagesSub?.unsubscribe();
            messagesSub = conversation.subscribeMessages((snapshot) => {
                messages.value = (snapshot ?? []).filter((message) => message.type === 'UserMessage').reverse();
                conversation.markAsRead().catch(() => undefined);
            });

            onlineSub?.unsubscribe();
            onlineSub = session.user(otherId).subscribeOnline((snapshot) => {
                activeOnline.value = snapshot?.isConnected ?? false;
            });
        } catch (e) {
            error.value = 'Não foi possível abrir a conversa.';
            console.error(e);
        }
    }

    async function sendMessage(text: string): Promise<void> {
        const trimmed = text.trim();

        if (!session || !activeOtherId.value || trimmed === '') {
            return;
        }

        try {
            error.value = null;
            await session.conversation(conversationIdFor(activeOtherId.value)).send(trimmed);
        } catch (e) {
            error.value = 'Não foi possível enviar a mensagem.';
            console.error(e);
        }
    }

    // Referência a uma mensagem da conversa aberta.
    // Reference to a message in the open conversation.
    function messageRef(messageId: string) {
        if (!session || !activeOtherId.value) {
            return null;
        }

        return session.conversation(conversationIdFor(activeOtherId.value)).message(messageId);
    }

    // Edita uma mensagem tua. Devolve true se correu bem.
    // Edits one of your messages. Returns true on success.
    async function editMessage(messageId: string, text: string): Promise<boolean> {
        const trimmed = text.trim();
        const message = messageRef(messageId);

        if (!message || trimmed === '') {
            return false;
        }

        try {
            error.value = null;
            await message.edit(trimmed);

            return true;
        } catch (e) {
            error.value = 'Não foi possível editar a mensagem.';
            console.error(e);

            return false;
        }
    }

    // Apaga uma mensagem tua. Devolve true se correu bem.
    // Deletes one of your messages. Returns true on success.
    async function deleteMessage(messageId: string): Promise<boolean> {
        const message = messageRef(messageId);

        if (!message) {
            return false;
        }

        try {
            error.value = null;
            await message.delete();

            return true;
        } catch (e) {
            error.value = 'Não foi possível apagar a mensagem.';
            console.error(e);

            return false;
        }
    }

    // Dá ou retira a tua reação a uma mensagem (de qualquer pessoa da conversa).
    // Adds or removes your reaction to a message (from anyone in the conversation).
    async function toggleReaction(messageId: string, emoji: string, alreadyReacted: boolean): Promise<void> {
        const message = messageRef(messageId);

        if (!message) {
            return;
        }

        try {
            error.value = null;
            const reaction = message.reactions(emoji);

            if (alreadyReacted) {
                await reaction.remove();
            } else {
                await reaction.add();
            }
        } catch (e) {
            error.value = 'Não foi possível reagir à mensagem.';
            console.error(e);
        }
    }

    onBeforeUnmount(() => {
        conversationsSub?.unsubscribe();
        messagesSub?.unsubscribe();
        onlineSub?.unsubscribe();
        userSubs.forEach((subscription) => subscription.unsubscribe());
        userSubs.clear();
    });

    return {
        me,
        items,
        messages,
        activeOtherId,
        activePerson,
        activeOnline,
        totalUnread,
        ready,
        error,
        start,
        openConversation,
        sendMessage,
        editMessage,
        deleteMessage,
        toggleReaction,
    };
}