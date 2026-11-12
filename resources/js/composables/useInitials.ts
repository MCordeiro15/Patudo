// Composable para gerar iniciais a partir do nome do utilizador, usado em avatares.
// Composable to generate initials from the user's name, used in avatars.
export type UseInitialsReturn = {
    getInitials: (fullName?: string) => string;
};

function getInitial(name: string): string {
    return Array.from(name)[0] ?? '';
}

export function getInitials(fullName?: string): string {
    if (!fullName) {
        return '';
    }

    const names = fullName.trim().split(/\s+/u).filter(Boolean);

    if (names.length === 0) {
        return '';
    }

    if (names.length === 1) {
        return getInitial(names[0]).toUpperCase();
    }

    return `${getInitial(names[0])}${getInitial(names[names.length - 1])}`.toUpperCase();
}

// Gera as iniciais do nome do utilizador para avatares.
// Generates user name initials for avatars.
export function useInitials(): UseInitialsReturn {
    return { getInitials };
}
