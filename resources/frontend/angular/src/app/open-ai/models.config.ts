// models.config.ts
export interface AIModel {
    provider: 'OpenAI' | 'Google Gemini';
    id: string;
    label: string;
    description: string;
}

export const AI_MODELS: AIModel[] = [
    // ==== OpenAI ====
    {
        provider: 'OpenAI',
        id: 'gpt-4.1',
        label: 'GPT-4.1',
        description: 'Flagship model with highest reasoning and capability.',
    },
    {
        provider: 'OpenAI',
        id: 'gpt-4.1-mini',
        label: 'GPT-4.1 Mini',
        description: 'Balanced performance and cost; cheaper than GPT-4.1.',
    },
    {
        provider: 'OpenAI',
        id: 'gpt-4.1-nano',
        label: 'GPT-4.1 Nano',
        description: 'Fastest and most economical; good for lightweight tasks.',
    },
    {
        provider: 'OpenAI',
        id: 'gpt-4o',
        label: 'GPT-4o',
        description: 'Multimodal (text, image, audio); top performance.',
    },
    {
        provider: 'OpenAI',
        id: 'gpt-4o-mini',
        label: 'GPT-4o Mini',
        description: 'Cheaper, faster multimodal; good for high-volume tasks.',
    },
    {
        provider: 'OpenAI',
        id: 'gpt-4-turbo',
        label: 'GPT-4 Turbo',
        description: 'Optimized for chat; good balance of speed and cost.',
    },
    {
        provider: 'OpenAI',
        id: 'gpt-3.5-turbo',
        label: 'GPT-3.5 Turbo',
        description: 'Lightweight and economical; great for simple tasks.',
    },

    // ==== Google Gemini ====
    {
        provider: 'Google Gemini',
        id: 'gemini-2.5-pro',
        label: 'Gemini 2.5 Pro',
        description: 'Highest reasoning and multimodal capability.',
    },
    {
        provider: 'Google Gemini',
        id: 'gemini-2.5-flash',
        label: 'Gemini 2.5 Flash',
        description: 'Balanced performance and cost; fast throughput.',
    },
    {
        provider: 'Google Gemini',
        id: 'gemini-2.5-flash-lite',
        label: 'Gemini 2.5 Flash-Lite',
        description: 'Economical and optimized for high-volume tasks.',
    },
    {
        provider: 'Google Gemini',
        id: 'gemini-1.5-pro',
        label: 'Gemini 1.5 Pro',
        description: 'Older generation; advanced reasoning.',
    },
    {
        provider: 'Google Gemini',
        id: 'gemini-1.5-flash',
        label: 'Gemini 1.5 Flash',
        description: 'Older generation; fast and versatile.',
    },
];
