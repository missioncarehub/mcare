export const CERTIFICATE_FONTS = [
    // Calligraphy / Script (ideal for trainee names & decorative accents)
    { key: 'GreatVibes', label: 'Great Vibes (Script)', category: 'script', file: 'GreatVibes-Regular.ttf' },
    { key: 'AlexBrush', label: 'Alex Brush (Script)', category: 'script', file: 'AlexBrush-Regular.ttf' },
    { key: 'PinyonScript', label: 'Pinyon Script (Calligraphy)', category: 'script', file: 'PinyonScript-Regular.ttf' },

    // Formal / Classic Serif (ideal for headings, programs & signatories)
    { key: 'Cinzel', label: 'Cinzel', category: 'serif', file: 'Cinzel-Regular.ttf' },
    { key: 'Cinzel-Bold', label: 'Cinzel Bold', category: 'serif', file: 'Cinzel-Bold.ttf' },
    { key: 'PlayfairDisplay', label: 'Playfair Display', category: 'serif', file: 'PlayfairDisplay-Regular.ttf' },
    { key: 'PlayfairDisplay-Bold', label: 'Playfair Display Bold', category: 'serif', file: 'PlayfairDisplay-Bold.ttf' },
    { key: 'PlayfairDisplay-Italic', label: 'Playfair Display Italic', category: 'serif', file: 'PlayfairDisplay-Italic.ttf' },
    { key: 'CormorantGaramond', label: 'Cormorant Garamond', category: 'serif', file: 'CormorantGaramond-Regular.ttf' },
    { key: 'CormorantGaramond-Bold', label: 'Cormorant Garamond Bold', category: 'serif', file: 'CormorantGaramond-Bold.ttf' },
    { key: 'LibreBaskerville', label: 'Libre Baskerville', category: 'serif', file: 'LibreBaskerville-Regular.ttf' },
    { key: 'LibreBaskerville-Bold', label: 'Libre Baskerville Bold', category: 'serif', file: 'LibreBaskerville-Bold.ttf' },

    // Clean Modern Sans-Serif (ideal for dates, doc numbers & TOR tables)
    { key: 'Montserrat', label: 'Montserrat', category: 'sans', file: 'Montserrat-Regular.ttf' },
    { key: 'Montserrat-Bold', label: 'Montserrat Bold', category: 'sans', file: 'Montserrat-Bold.ttf' },
    { key: 'NotoSans', label: 'Noto Sans (Default)', category: 'sans', file: 'NotoSans-Regular.ttf', fallback: true },
];

export function getBrowserPdfmeFonts() {
    const map = {};
    for (const font of CERTIFICATE_FONTS) {
        map[font.key] = {
            data: `/fonts/certificate/${font.file}`,
            ...(font.fallback ? { fallback: true } : {}),
        };
    }
    return map;
}

export const CERTIFICATE_STYLE_PRESETS = {
    classic_formal: {
        label: 'Classic Formal',
        description: 'Playfair Display + Cinzel + Cormorant',
        roles: {
            name: 'PlayfairDisplay-Bold',
            program: 'Cinzel-Bold',
            statement: 'CormorantGaramond',
            date: 'CormorantGaramond',
            meta: 'Montserrat',
            signatory: 'Cinzel-Bold',
            default: 'CormorantGaramond',
        },
    },
    calligraphy_honor: {
        label: 'Calligraphy Honor',
        description: 'Great Vibes Script + Cinzel + Montserrat',
        roles: {
            name: 'GreatVibes',
            program: 'Cinzel-Bold',
            statement: 'PlayfairDisplay-Italic',
            date: 'CormorantGaramond',
            meta: 'Montserrat',
            signatory: 'PlayfairDisplay-Bold',
            default: 'Montserrat',
        },
    },
    modern_executive: {
        label: 'Modern Executive',
        description: 'Libre Baskerville + Montserrat',
        roles: {
            name: 'LibreBaskerville-Bold',
            program: 'Montserrat-Bold',
            statement: 'Montserrat',
            date: 'Montserrat',
            meta: 'Montserrat',
            signatory: 'LibreBaskerville-Bold',
            default: 'Montserrat',
        },
    },
};
