import { intlFormatDistance } from 'date-fns';

export const formatDateTime = (date) => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(date).toLocaleDateString('id-ID', options);
};

export const formatDateTimeComment = (date) => {
    return intlFormatDistance(new Date(date), new Date(), { addSuffix: true });
};