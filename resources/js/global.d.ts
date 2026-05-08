// types/global.d.ts
import Echo from 'laravel-echo';

declare global {
  interface Window {
    Echo: Echo;
  }
}

export interface AuthUser {
  id: number
  name: string
  email: string
  roles: string
  site_id?: number
  permissions: string
}

export interface PageProps {
  [key: string]: unknown

  auth: {
    user?: AuthUser | null
  }

  flash?: {
    success?: string
    reason?: string
  }
}