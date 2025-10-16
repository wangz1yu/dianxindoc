export interface DocumentSignature {
    id?: string;
    documentId: string;
    createdBy?: string;
    signatureUrl: string;
    createdDate?: Date;
    base64?: string;
    signatureBy?: string; // 'drawn' | 'uploaded'
}
