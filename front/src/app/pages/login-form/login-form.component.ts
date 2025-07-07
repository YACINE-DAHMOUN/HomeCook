import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, AbstractControl, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { RegisterRequest } from '../../models/user.interface';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-login-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule], // Add ReactiveFormsModule
  templateUrl: './login-form.component.html',
  styleUrls: ['./login-form.component.css']
})
export class LoginFormComponent implements OnInit { // Fixed class name capitalization
  registerForm!: FormGroup;
  isLoading = false;
  errorMessage = '';
  showPassword = false;
  showConfirmPassword = false;
  passwordStrength = 0;
  passwordStrengthText = '';
  passwordStrengthColor = '';

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.initializeForm();
    this.setupPasswordValidation();
  }

  private initializeForm(): void {
    this.registerForm = this.fb.group({
      username: ['', [
        Validators.required,
        Validators.minLength(3),
        Validators.maxLength(30),
        Validators.pattern(/^[a-zA-Z0-9_]+$/)
      ]],
      firstName: ['', [Validators.required, Validators.maxLength(255)]],
      lastName: ['', [Validators.required, Validators.maxLength(255)]],
      email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
      password: ['', [
        Validators.required,
        Validators.minLength(8),
        this.passwordValidator
      ]],
      confirmPassword: ['', [Validators.required]],
      terms: [false, [Validators.requiredTrue]]
    }, {
      validators: this.passwordMatchValidator
    });
  }

  private setupPasswordValidation(): void {
    this.registerForm.get('password')?.valueChanges.subscribe(password => {
      if (password) {
        this.calculatePasswordStrength(password);
      }
    });
  }

  private passwordValidator(control: AbstractControl): {[key: string]: any} | null {
    const password = control.value;
    if (!password) return null;

    const hasLowerCase = /[a-z]/.test(password);
    const hasUpperCase = /[A-Z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasMinLength = password.length >= 8;

    const valid = hasLowerCase && hasUpperCase && hasNumber && hasMinLength;
    return valid ? null : { 'passwordInvalid': true };
  }

  private passwordMatchValidator(group: AbstractControl): {[key: string]: any} | null {
    const password = group.get('password')?.value;
    const confirmPassword = group.get('confirmPassword')?.value;
    return password === confirmPassword ? null : { 'passwordMismatch': true };
  }

  private calculatePasswordStrength(password: string): void {
    let strength = 0;
    const feedback: string[] = [];

    if (password.length >= 8) strength += 25;
    else feedback.push('au moins 8 caractères');

    if (/[a-z]/.test(password)) strength += 25;
    else feedback.push('une minuscule');

    if (/[A-Z]/.test(password)) strength += 25;
    else feedback.push('une majuscule');

    if (/[0-9]/.test(password)) strength += 25;
    else feedback.push('un chiffre');

    this.passwordStrength = strength;

    if (strength < 50) {
      this.passwordStrengthText = 'Faible - Manque: ' + feedback.join(', ');
      this.passwordStrengthColor = 'bg-red-500 text-red-600';
    } else if (strength < 100) {
      this.passwordStrengthText = 'Moyen - Manque: ' + feedback.join(', ');
      this.passwordStrengthColor = 'bg-yellow-500 text-yellow-600';
    } else {
      this.passwordStrengthText = 'Fort - Mot de passe sécurisé';
      this.passwordStrengthColor = 'bg-green-500 text-green-600';
    }
  }

  togglePassword(field: 'password' | 'confirmPassword'): void {
    if (field === 'password') {
      this.showPassword = !this.showPassword;
    } else {
      this.showConfirmPassword = !this.showConfirmPassword;
    }
  }

  onSubmit(): void {
    if (this.registerForm.valid && !this.isLoading) {
      this.isLoading = true;
      this.errorMessage = '';

      const registerData: RegisterRequest = {
        username: this.registerForm.value.username,
        firstName: this.registerForm.value.firstName,
        lastName: this.registerForm.value.lastName,
        email: this.registerForm.value.email,
        password: this.registerForm.value.password
      };

      this.authService.register(registerData).subscribe({
        next: (response: any) => {
          this.isLoading = false;
          this.router.navigate(['/dashboard']);
        },
        error: (error: string) => {
          this.isLoading = false;
          this.errorMessage = error;
        }
      });
    } else {
      this.markFormGroupTouched();
    }
  }

  private markFormGroupTouched(): void {
    Object.keys(this.registerForm.controls).forEach(key => {
      const control = this.registerForm.get(key);
      control?.markAsTouched();
    });
  }

  // Getters for easier access to controls in template
  get username() { return this.registerForm.get('username'); }
  get firstName() { return this.registerForm.get('firstName'); }
  get lastName() { return this.registerForm.get('lastName'); }
  get email() { return this.registerForm.get('email'); }
  get password() { return this.registerForm.get('password'); }
  get confirmPassword() { return this.registerForm.get('confirmPassword'); }
  get terms() { return this.registerForm.get('terms'); }

  hasError(controlName: string, errorType: string): boolean {
    const control = this.registerForm.get(controlName);
    return !!(control?.hasError(errorType) && control?.touched);
  }

  getErrorMessage(controlName: string): string {
    const control = this.registerForm.get(controlName);
    if (control?.errors && control?.touched) {
      if (control.errors['required']) return `${controlName} est requis`;
      if (control.errors['email']) return 'Format d\'email invalide';
      if (control.errors['minlength']) return `Minimum ${control.errors['minlength'].requiredLength} caractères`;
      if (control.errors['maxlength']) return `Maximum ${control.errors['maxlength'].requiredLength} caractères`;
      if (control.errors['pattern']) return 'Format invalide';
      if (control.errors['passwordInvalid']) return 'Le mot de passe doit contenir majuscules, minuscules et chiffres';
    }

    if (controlName === 'confirmPassword' && this.registerForm.errors?.['passwordMismatch']) {
      return 'Les mots de passe ne correspondent pas';
    }

    return '';
  }
}
