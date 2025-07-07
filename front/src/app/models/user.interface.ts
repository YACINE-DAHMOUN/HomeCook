export interface User {
  id?: string;
  username: string;
  firstName: string;
  lastName: string;
  email: string;
  password?: string;
}

export interface RegisterRequest {
  username: string;
  firstName: string;
  lastName: string;
  email: string;
  password: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface AuthResponse {
  token: string;
  refreshToken?: string;
  user: User;
  expires?: number;
}
