import { LanguageFlag } from './language-flag';

export class CompanyProfile {
  id?: string;
  title: string;
  logoUrl?: string;
  smallLogoUrl?: string;
  bannerUrl?: string;
  imageData?: string;
  smallLogoData?: string;
  location = 'local';
  isS3Supported = false;
  languages?: LanguageFlag[];
  archiveDocumentRetensionPeriod?: number;
  allowPdfSignature = true;
  emailLogRetentionPeriod?: number;
  cronJobLogRetentionPeriod?: number;
  loginAuditRetentionPeriod?: number;
}

export class S3Config {
  location: string;
  amazonS3key: string;
  amazonS3secret: string;
  amazonS3region: string;
  amazonS3bucket: string;
}

export class OpenAiApi {
  openApiKey: string;
}

export class GoogleGeminiApi {
  googleGeminiApiKey: string;
}
