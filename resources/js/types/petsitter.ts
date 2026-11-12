

export type PetsitterProfile = {
    id: number;
    user_id: number;
    name: string;
    profile_photo: string | null;
    biography: string | null;
    experience: string;
    service_location: string;
    age: number;
    price_per_day: number;
    accepted_sizes: string[] | null;
    accepted_animal_types: string[] | null;
    sex: string | null;
    approval_status: string;
    identification_documents?: string | null;
    availability_days: string[] | null;
    created_at: string;
    updated_at: string;
};

export type PetsitterFormErrors = {
    biography?: string[];
    experience?: string[];
    service_location?: string[];
    price_per_day?: string[];
    age?: string[];
    sex?: string[];
    accepted_sizes?: string[];
    accepted_animal_types?: string[];
    [key: string]: string[] | undefined;
};
