import { apiRequest } from './api'

export interface SharedProfileData { name: string; programme?: string; headline?: string; sections: Array<{key:string;title:string;records:Array<{title:string;detail:string;date?:string;tags?:string[]}>}> }
export interface ProfileShare {id:number;path:string|null;selection:string[];expiresAt:string;active:boolean;revoked:boolean}
export const shareFields = [{value:'programme',label:'Programme'},{value:'headline',label:'Profile headline'},{value:'skills',label:'Verified skills'},{value:'projects',label:'Verified projects and descriptions'},{value:'certifications',label:'Current certifications'},{value:'assessments',label:'Best assessment results'}]
export function listShares(){return apiRequest<{data:ProfileShare[]}>('/api/student/profile-shares')}
export function previewShare(selection:string[]){return apiRequest<SharedProfileData>('/api/student/profile-shares/preview',{method:'POST',body:JSON.stringify({selection})})}
export function createShare(selection:string[],days:number){return apiRequest<ProfileShare>('/api/student/profile-shares',{method:'POST',body:JSON.stringify({selection,days})})}
export function revokeShare(id:number){return apiRequest(`/api/student/profile-shares/${id}`,{method:'DELETE'})}
