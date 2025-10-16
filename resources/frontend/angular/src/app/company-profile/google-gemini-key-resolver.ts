import { CompanyProfileService } from "./company-profile.service";
import { inject } from "@angular/core";
import { ResolveFn } from "@angular/router";

export const googleGeminiApiKeyResolver: ResolveFn<any> = (_route, _state) => {
    const companyProfileService = inject(CompanyProfileService);
    return companyProfileService.getGoogleGeminiApiKey();
};