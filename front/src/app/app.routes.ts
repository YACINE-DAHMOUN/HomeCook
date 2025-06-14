import { User } from './models/user.interface';
import { LoginFormComponent } from './pages/login-form/login-form.component';
import { Routes } from '@angular/router';
import { LoginComponent } from './pages/login/login.component';
import { UserPagesComponent } from './pages/user-pages/user-pages.component';
import { HomeComponent } from './pages/home/home.component';


export const routes: Routes = [
  {path: 'login', component: LoginComponent },
  {path: 'login-form', component: LoginFormComponent },
  {path: 'user-pages', component: UserPagesComponent },
  {path: 'home', component: HomeComponent }

];


