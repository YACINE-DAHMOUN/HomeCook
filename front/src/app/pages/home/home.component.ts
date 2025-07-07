import { RecipesService, Recipe } from './../../services/recipes.services';
import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css'],
  standalone: true,
  imports: [
    CommonModule,
    RouterModule,
  ]
})
export class HomeComponent implements OnInit {
  recipes: Recipe[] = [];
  loading = true;
  error = '';

  constructor(private recipesService: RecipesService) { }

  ngOnInit(): void {
    this.loadRecipesPreview();
  }

  loadRecipesPreview(): void {
    this.loading = true;
    this.recipesService.getRecipesPreview(6).subscribe({
      next: (response) => {
        // API Platform retourne les données dans response['hydra:member']
        this.recipes = response['hydra:member'] || response;
        this.loading = false;
      },
      error: (error) => {
        console.error('Error fetching recipes preview:', error);
        this.error = 'Impossible de charger les recettes';
        this.loading = false;
      }
    });
  }

  formatCookingTime(minutes: number): string {
    if (minutes < 60) {
      return `${minutes} min`;
    }
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return remainingMinutes > 0 ? `${hours}h ${remainingMinutes}min` : `${hours}h`;
  }
}
