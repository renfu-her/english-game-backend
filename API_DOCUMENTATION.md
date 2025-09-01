# English Game Backend API Documentation

## Overview

This API provides a comprehensive multiplayer English game system with real-time features. The system supports both single-player and multiplayer game modes with room-based multiplayer sessions.

## Authentication

All protected endpoints require authentication using Laravel Sanctum tokens.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

## API Endpoints

### 1. Authentication

#### Login
```http
POST /api/auth/login
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password"
}
```

#### Register
```http
POST /api/auth/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

#### Get Profile
```http
GET /api/auth/profile
```

#### Logout
```http
POST /api/auth/logout
```

---

### 2. Categories

#### Get All Categories
```http
GET /api/categories
```

#### Get Category Details
```http
GET /api/categories/{id}
```

#### Get Category Questions
```http
GET /api/categories/{id}/questions
```

---

### 3. Questions

#### Get All Questions
```http
GET /api/questions
```

#### Get Random Question
```http
GET /api/questions/random
```

#### Get Question Details
```http
GET /api/questions/{id}
```

---

### 4. Single-Player Game

#### Submit Answer
```http
POST /api/game/submit-answer
```

**Request Body:**
```json
{
    "question_id": 1,
    "user_answer": "correct answer",
    "time_taken": 25
}
```

#### Get Progress
```http
GET /api/game/progress
```

#### Get Leaderboard
```http
GET /api/game/leaderboard?limit=10
```

#### Get Stats
```http
GET /api/game/stats
```

---

### 5. Game Rooms (Multiplayer)

#### Get Available Game Rooms
```http
GET /api/game-rooms
```

**Query Parameters:**
- `status`: `waiting`, `playing`, `finished`
- `category_id`: Filter by category
- `page`: Page number
- `per_page`: Items per page (max 50)

#### Create Game Room
```http
POST /api/game-rooms
```

**Request Body:**
```json
{
    "name": "My Game Room",
    "category_id": 1,
    "max_players": 6,
    "total_rounds": 10,
    "time_per_question": 30,
    "settings": {
        "custom_setting": "value"
    }
}
```

#### Find Room by Code
```http
GET /api/game-rooms/find-by-code?code=ABC123
```

#### Get Room Details
```http
GET /api/game-rooms/{id}
```

#### Join Room
```http
POST /api/game-rooms/{id}/join
```

#### Leave Room
```http
POST /api/game-rooms/{id}/leave
```

#### Toggle Ready Status
```http
POST /api/game-rooms/{id}/toggle-ready
```

#### Start Game (Room Owner Only)
```http
POST /api/game-rooms/{id}/start
```

#### End Game (Room Owner Only)
```http
POST /api/game-rooms/{id}/end
```

#### Submit Answer in Room
```http
POST /api/game-rooms/{id}/submit-answer
```

**Request Body:**
```json
{
    "user_answer": "correct answer",
    "time_taken": 25
}
```

#### Get Room Leaderboard
```http
GET /api/game-rooms/{id}/leaderboard
```

---

### 6. Game Session Management

#### Get Game State
```http
GET /api/game-sessions/{roomId}/state
```

**Response:**
```json
{
    "success": true,
    "data": {
        "room": {
            "id": 1,
            "name": "My Game Room",
            "status": "playing",
            "current_round": 3,
            "total_rounds": 10,
            "time_per_question": 30,
            "started_at": "2024-01-01T12:00:00Z"
        },
        "current_question": {
            "id": 15,
            "question_text": "What is the past tense of 'go'?",
            "options": ["went", "goed", "gone", "going"],
            "difficulty": "easy"
        },
        "players": [
            {
                "id": 1,
                "name": "John Doe",
                "current_score": 85,
                "answers_correct": 8,
                "answers_incorrect": 2,
                "is_ready": true,
                "accuracy_rate": 80.0
            }
        ],
        "my_stats": {
            "current_score": 85,
            "answers_correct": 8,
            "answers_incorrect": 2,
            "is_ready": true,
            "accuracy_rate": 80.0
        },
        "has_answered_current_question": false
    }
}
```

#### Next Question (Room Owner Only)
```http
POST /api/game-sessions/{roomId}/next-question
```

#### Pause Game (Room Owner Only)
```http
POST /api/game-sessions/{roomId}/pause
```

#### Resume Game (Room Owner Only)
```http
POST /api/game-sessions/{roomId}/resume
```

#### Skip Question (Room Owner Only)
```http
POST /api/game-sessions/{roomId}/skip-question
```

#### Get Question Results
```http
GET /api/game-sessions/{roomId}/question-results
```

#### Get Game Summary
```http
GET /api/game-sessions/{roomId}/summary
```

**Response:**
```json
{
    "success": true,
    "data": {
        "game_room": {
            "id": 1,
            "name": "My Game Room",
            "status": "finished"
        },
        "final_rankings": [
            {
                "rank": 1,
                "member": {
                    "id": 1,
                    "name": "John Doe"
                },
                "final_score": 150,
                "answers_correct": 12,
                "answers_incorrect": 3,
                "accuracy_rate": 80.0
            }
        ],
        "my_performance": {
            "final_score": 150,
            "total_questions": 15,
            "correct_answers": 12,
            "accuracy_rate": 80.0,
            "average_time": 22.5,
            "fastest_answer": 8,
            "slowest_answer": 45,
            "my_rank": 1
        },
        "game_statistics": {
            "total_players": 4,
            "total_questions": 15,
            "game_duration": 25,
            "category": "Grammar"
        }
    }
}
```

---

## Game Room States

### Room Status
- `waiting`: Room is open for players to join
- `playing`: Game is actively running
- `paused`: Game is temporarily paused
- `finished`: Game has ended

### Player Ready States
- Players must mark themselves as "ready" before the game can start
- Room owner can start the game when all players are ready
- Minimum 2 players required to start

### Game Flow
1. **Room Creation**: Owner creates room with settings
2. **Player Joining**: Players join using room code or from room list
3. **Ready Check**: All players mark themselves as ready
4. **Game Start**: Owner starts the game
5. **Question Rounds**: Players answer questions in rounds
6. **Game End**: Game ends after all rounds or owner ends it
7. **Results**: Final scores and rankings displayed

---

## Real-time Communication (WebSocket)

### Events

The system supports real-time events for live game updates:

#### Game Room Events
- `game.room.updated`: Room state changes (players join/leave, ready status)
- `game.started`: Game begins
- `game.paused`: Game is paused
- `game.resumed`: Game is resumed
- `game.ended`: Game ends
- `question.next`: New question is presented
- `answer.submitted`: Player submits an answer

#### Channels
- `game-room.{roomId}`: Private channel for specific room events

### WebSocket Authentication
Players must authenticate to join private channels for their game rooms.

---

## Error Handling

### Standard Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

### Common HTTP Status Codes
- `200`: Success
- `400`: Bad Request (validation errors, business logic violations)
- `401`: Unauthorized (invalid or missing token)
- `403`: Forbidden (insufficient permissions)
- `404`: Not Found (resource doesn't exist)
- `500`: Internal Server Error

---

## Rate Limiting

API endpoints are protected by rate limiting:
- Authentication endpoints: 60 requests per minute
- Game endpoints: 100 requests per minute
- Other endpoints: 60 requests per minute

---

## Security Features

### Authentication
- JWT tokens via Laravel Sanctum
- Secure password hashing
- Token expiration and refresh

### Authorization
- Room ownership verification
- Player membership validation
- Action permission checks

### Validation
- Input sanitization
- Data type validation
- Business rule enforcement

---

## Development Notes

### Database Models
- `Member`: Player accounts
- `GameRoom`: Multiplayer game sessions
- `GameRoomPlayer`: Player participation in rooms
- `GameRoomResult`: Individual answer results
- `Question`: Game questions
- `Category`: Question categories

### Key Features
- Player limits (max 6 players per room)
- Room codes for easy joining
- Real-time score updates
- Comprehensive game statistics
- Pause/resume functionality
- Question randomization
- Time-based scoring bonuses

### Future Enhancements
- Tournament modes
- Custom question sets
- Advanced statistics
- Mobile push notifications
- Voice chat integration
