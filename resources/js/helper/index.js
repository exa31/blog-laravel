import { intlFormatDistance, format } from 'date-fns';

export const formatDateTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return format(d, 'MMMM d, yyyy');
};

export const formatShortDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return format(d, 'MMM d, yyyy');
};

export const formatDateTimeComment = (date) => {
    if (!date) return 'just now';
    try {
        return intlFormatDistance(new Date(date), new Date(), { addSuffix: true });
    } catch {
        return 'just now';
    }
};

export const getImageUrl = (image) => {
    if (!image) return 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80';

    if (typeof image === 'object') {
        if (image.image_url) return image.image_url;
        image = image.image_thumbnail;
    }

    if (!image) return 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=800&q=80';

    if (image.startsWith('http://') || image.startsWith('https://')) {
        return image;
    }

    return `/storage/${image.replace(/^\/?storage\//, '')}`;
};