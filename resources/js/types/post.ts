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
