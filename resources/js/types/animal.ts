export type Animal = {
    id: number;
    user_id: number;
    name: string;
    species: string;
    breed: string;
    size: 'small' | 'medium' | 'large';
    special_needs: string | null;
    aggression: string | null;
    medication: string | null;
    sex: string | null;
    photo: string | null;
    weight: number | null;
    created_at: string;
    updated_at: string;
};

export type AnimalFormErrors = {
    name?: string[];
    species?: string[];
    breed?: string[];
    size?: string[];
    special_needs?: string[];
    aggression?: string[];
    medication?: string[];
    sex?: string[];
    photo?: string[];
    weight?: string[];
    [key: string]: string[] | undefined;
};
