# English Learning Game API

A Laravel-based API for an English learning game with multiple categories, questions, and member management.

## Features

- **Member Authentication**: Register, login, and manage member accounts
- **Multiple Categories**: Different difficulty levels and topics
- **Question Types**: Multiple choice and fill-in-the-blank questions
- **Scoring System**: Track scores, levels, and progress
- **Leaderboard**: Compare performance with other members
- **Admin Panel**: Filament v4 admin interface for content management

## Installation

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env` and configure your database
4. Run migrations: `php artisan migrate`
5. Seed the database: `php artisan db:seed`
6. Create admin user: `php artisan make:filament-user`

## Database Structure

### Tables

- **users**: Admin users for Filament panel
- **members**: Game players with scores and levels
- **categories**: Question categories with difficulty levels
- **questions**: Questions with multiple choice or fill-in-the-blank format
- **game_results**: Individual question attempts and scores
- **category_progress**: Member progress tracking per category

### Sample Data

The seeder creates:
- 1 admin user (admin@example.com / admin123)
- 5 member accounts (member1@example.com to member5@example.com / password)
- 5 categories with different difficulty levels
- 25 questions (5 per category) with mixed question types

## API Endpoints

### Authentication

#### Register Member
```
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Login Member
```
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

#### Logout Member
```
POST /api/auth/logout
Authorization: Bearer {token}
```

#### Get Profile
```
GET /api/auth/profile
Authorization: Bearer {token}
```

### Categories

#### Get All Categories
```
GET /api/categories
Authorization: Bearer {token}
```

#### Get Category Details
```
GET /api/categories/{id}
Authorization: Bearer {token}
```

#### Get Category Questions
```
GET /api/categories/{id}/questions?limit=10
Authorization: Bearer {token}
```

### Questions

#### Get Questions
```
GET /api/questions?category_id=1&type=multiple_choice&limit=10
Authorization: Bearer {token}
```

#### Get Random Question
```
GET /api/questions/random?category_id=1&type=multiple_choice
Authorization: Bearer {token}
```

#### Get Question Details
```
GET /api/questions/{id}
Authorization: Bearer {token}
```

### Game

#### Submit Answer
```
POST /api/game/submit-answer
Authorization: Bearer {token}
Content-Type: application/json

{
    "question_id": 1,
    "user_answer": "cold",
    "time_taken": 15
}
```

#### Get Progress
```
GET /api/game/progress
Authorization: Bearer {token}
```

#### Get Leaderboard
```
GET /api/game/leaderboard?limit=10
Authorization: Bearer {token}
```

#### Get Stats
```
GET /api/game/stats
Authorization: Bearer {token}
```

## Question Types

### Multiple Choice
```json
{
    "id": 1,
    "question_text": "What is the opposite of 'hot'?",
    "question_type": "multiple_choice",
    "correct_answer": "cold",
    "options": ["cold", "warm", "cool", "freezing"],
    "explanation": "The opposite of hot is cold.",
    "difficulty_level": 1
}
```

### Fill in the Blank
```json
{
    "id": 2,
    "question_text": "Complete the sentence: 'The sun is _____ today.'",
    "question_type": "fill_blank",
    "correct_answer": "bright",
    "options": null,
    "explanation": "Bright is used to describe strong sunlight.",
    "difficulty_level": 1
}
```

## Scoring System

- **Correct Answer**: 10 points
- **Quick Answer Bonus**: +5 points (if answered in under 30 seconds)
- **Level Up**: Every 100 points = 1 level increase

## Admin Panel

Access the Filament admin panel at `/admin` with the credentials created during setup.

### Admin Features

- **User Management**: Manage admin users
- **Member Management**: View and manage game members
- **Category Management**: Create and edit question categories
- **Question Management**: Add, edit, and organize questions
- **Game Results**: View member performance and statistics

## Models

### Member
- `name`: Member's display name
- `email`: Unique email address
- `password`: Hashed password
- `score`: Total accumulated score
- `level`: Current level (1-100)

### Category
- `name`: Category name
- `description`: Category description
- `difficulty_level`: 1-5 difficulty rating
- `is_active`: Whether category is available

### Question
- `category_id`: Associated category
- `question_text`: The question content
- `question_type`: 'multiple_choice' or 'fill_blank'
- `correct_answer`: The correct answer
- `options`: JSON array for multiple choice options
- `explanation`: Explanation of the answer
- `difficulty_level`: 1-5 difficulty rating
- `is_active`: Whether question is available

### GameResult
- `member_id`: Member who answered
- `question_id`: Question that was answered
- `category_id`: Category of the question
- `user_answer`: Member's submitted answer
- `is_correct`: Whether answer was correct
- `time_taken`: Time taken to answer (seconds)
- `score_earned`: Points earned for this answer

### CategoryProgress
- `member_id`: Member
- `category_id`: Category
- `questions_attempted`: Total questions attempted
- `questions_correct`: Correct answers
- `total_score`: Total score in this category
- `completion_percentage`: Percentage of correct answers
- `last_played_at`: Last time category was played

## Frontend Integration

This API is designed to work with a Vue.js frontend. The API provides:

- JWT token authentication via Laravel Sanctum
- RESTful endpoints for all game functionality
- JSON responses with consistent structure
- Error handling with appropriate HTTP status codes

## Development

### Adding New Categories
1. Use the admin panel or create a new seeder
2. Add questions to the category
3. Set appropriate difficulty levels

### Adding New Question Types
1. Extend the `question_type` enum in the migration
2. Update the Question model validation
3. Modify the GameController logic if needed

### Customizing Scoring
Modify the `submitAnswer` method in `GameController` to adjust:
- Base points per correct answer
- Bonus point conditions
- Level up thresholds

## Security

- All API endpoints (except login/register) require authentication
- Passwords are hashed using Laravel's built-in hashing
- CSRF protection is enabled for web routes
- Input validation on all endpoints

## Testing

Test the API using tools like Postman or curl:

```bash
# Login
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"member1@example.com","password":"password"}'

# Get categories
curl -X GET http://localhost/api/categories \
  -H "Authorization: Bearer {token}"
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
