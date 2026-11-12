// Definição de tipo TypeScript para Post. Inclui id, título, descrição, fotos e dados do petsitter autor.
// TypeScript type definition for Post. Includes id, title, description, photos, and author petsitter data.
export type Post = {
    id: number;
    petsitter_id: number;
    title: string | null;
    description: string;
    photos: string[];
    created_at: string | null;
};

export type PostFormErrors = {
    title?: string[];
    description?: string[];
    photos?: string[];
    [key: string]: string[] | undefined;
};
