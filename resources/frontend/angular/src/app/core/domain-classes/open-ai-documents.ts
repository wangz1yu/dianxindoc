
export interface OpenAiDocuments {
  id?: string;
  prompt: string;
  response: string;
  model?: string;
  language?: string;
  maximumLength?: number;
  creativity?: number;
  toneOfVoice?: string;
}

