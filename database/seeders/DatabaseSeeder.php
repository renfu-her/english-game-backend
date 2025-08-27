<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Member;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
        ]);

        // Create 5 members
        for ($i = 1; $i <= 5; $i++) {
            Member::create([
                'name' => "Member {$i}",
                'email' => "member{$i}@example.com",
                'password' => Hash::make('password'),
                'score' => rand(0, 500),
                'level' => rand(1, 5),
            ]);
        }

        // Create categories
        $categories = [
            [
                'name' => 'Basic Vocabulary',
                'description' => 'Essential English words for beginners',
                'difficulty_level' => 1,
            ],
            [
                'name' => 'Grammar Basics',
                'description' => 'Fundamental grammar rules and structures',
                'difficulty_level' => 2,
            ],
            [
                'name' => 'Common Phrases',
                'description' => 'Everyday expressions and idioms',
                'difficulty_level' => 2,
            ],
            [
                'name' => 'Business English',
                'description' => 'Professional vocabulary and expressions',
                'difficulty_level' => 3,
            ],
            [
                'name' => 'Advanced Grammar',
                'description' => 'Complex grammatical structures',
                'difficulty_level' => 4,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }

        // Create questions for each category
        $this->createBasicVocabularyQuestions();
        $this->createGrammarBasicsQuestions();
        $this->createCommonPhrasesQuestions();
        $this->createBusinessEnglishQuestions();
        $this->createAdvancedGrammarQuestions();
    }

    private function createBasicVocabularyQuestions()
    {
        $category = Category::where('name', 'Basic Vocabulary')->first();
        
        $questions = [
            [
                'question_text' => 'What is the opposite of "hot"?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'cold',
                'options' => ['cold', 'warm', 'cool', 'freezing'],
                'explanation' => 'The opposite of hot is cold.',
                'difficulty_level' => 1,
            ],
            [
                'question_text' => 'Complete the sentence: "The sun is _____ today."',
                'question_type' => 'fill_blank',
                'correct_answer' => 'bright',
                'options' => null,
                'explanation' => 'Bright is used to describe strong sunlight.',
                'difficulty_level' => 1,
            ],
            [
                'question_text' => 'Which word means "a place where you live"?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'home',
                'options' => ['home', 'house', 'building', 'apartment'],
                'explanation' => 'Home refers to where you live, while house is the physical structure.',
                'difficulty_level' => 1,
            ],
            [
                'question_text' => 'Fill in the blank: "I like to _____ books."',
                'question_type' => 'fill_blank',
                'correct_answer' => 'read',
                'options' => null,
                'explanation' => 'Read is the verb used for consuming written content.',
                'difficulty_level' => 1,
            ],
            [
                'question_text' => 'What color is the sky on a clear day?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'blue',
                'options' => ['blue', 'green', 'red', 'yellow'],
                'explanation' => 'The sky appears blue due to the scattering of sunlight.',
                'difficulty_level' => 1,
            ],
        ];

        foreach ($questions as $questionData) {
            $questionData['category_id'] = $category->id;
            Question::create($questionData);
        }
    }

    private function createGrammarBasicsQuestions()
    {
        $category = Category::where('name', 'Grammar Basics')->first();
        
        $questions = [
            [
                'question_text' => 'Which sentence is grammatically correct?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'I am going to the store.',
                'options' => ['I going to the store.', 'I am going to the store.', 'I goes to the store.', 'I go to the store.'],
                'explanation' => 'The present continuous tense requires "am/is/are + verb-ing".',
                'difficulty_level' => 2,
            ],
            [
                'question_text' => 'Complete: "She _____ to school every day."',
                'question_type' => 'fill_blank',
                'correct_answer' => 'goes',
                'options' => null,
                'explanation' => 'Third person singular requires adding -s to the verb.',
                'difficulty_level' => 2,
            ],
            [
                'question_text' => 'What is the plural form of "child"?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'children',
                'options' => ['childs', 'children', 'childes', 'child'],
                'explanation' => 'Child is an irregular noun, its plural is children.',
                'difficulty_level' => 2,
            ],
            [
                'question_text' => 'Fill in: "They _____ watching TV."',
                'question_type' => 'fill_blank',
                'correct_answer' => 'are',
                'options' => null,
                'explanation' => 'They is plural, so we use "are" with the present continuous.',
                'difficulty_level' => 2,
            ],
            [
                'question_text' => 'Which word is a pronoun?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'he',
                'options' => ['happy', 'he', 'quickly', 'book'],
                'explanation' => 'He is a personal pronoun that replaces a male noun.',
                'difficulty_level' => 2,
            ],
        ];

        foreach ($questions as $questionData) {
            $questionData['category_id'] = $category->id;
            Question::create($questionData);
        }
    }

    private function createCommonPhrasesQuestions()
    {
        $category = Category::where('name', 'Common Phrases')->first();
        
        $questions = [
            [
                'question_text' => 'What does "How are you?" mean?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'Asking about someone\'s well-being',
                'options' => ['Asking about someone\'s well-being', 'Asking for directions', 'Asking the time', 'Asking for help'],
                'explanation' => 'How are you? is a common greeting asking about someone\'s health or well-being.',
                'difficulty_level' => 2,
            ],
            [
                'question_text' => 'Complete: "Nice to _____ you."',
                'question_type' => 'fill_blank',
                'correct_answer' => 'meet',
                'options' => null,
                'explanation' => 'Nice to meet you is a common phrase when meeting someone for the first time.',
                'difficulty_level' => 2,
            ],
            [
                'question_text' => 'What does "See you later" mean?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'Goodbye, we will meet again',
                'options' => ['Goodbye, we will meet again', 'I can see you', 'Look at you', 'I will call you'],
                'explanation' => 'See you later is a casual way to say goodbye.',
                'difficulty_level' => 2,
            ],
            [
                'question_text' => 'Fill in: "Thank you _____ much."',
                'question_type' => 'fill_blank',
                'correct_answer' => 'very',
                'options' => null,
                'explanation' => 'Thank you very much is a polite way to express gratitude.',
                'difficulty_level' => 2,
            ],
            [
                'question_text' => 'What does "Excuse me" mean?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'A polite way to get attention or apologize',
                'options' => ['A polite way to get attention or apologize', 'I am sorry', 'Goodbye', 'Hello'],
                'explanation' => 'Excuse me is used to politely get someone\'s attention or apologize for interrupting.',
                'difficulty_level' => 2,
            ],
        ];

        foreach ($questions as $questionData) {
            $questionData['category_id'] = $category->id;
            Question::create($questionData);
        }
    }

    private function createBusinessEnglishQuestions()
    {
        $category = Category::where('name', 'Business English')->first();
        
        $questions = [
            [
                'question_text' => 'What does "deadline" mean in business?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'The final date by which something must be completed',
                'options' => ['The final date by which something must be completed', 'A line that is dead', 'A meeting time', 'A lunch break'],
                'explanation' => 'A deadline is the latest time or date by which something should be completed.',
                'difficulty_level' => 3,
            ],
            [
                'question_text' => 'Complete: "I would like to _____ a meeting."',
                'question_type' => 'fill_blank',
                'correct_answer' => 'schedule',
                'options' => null,
                'explanation' => 'To schedule a meeting means to arrange a time for it.',
                'difficulty_level' => 3,
            ],
            [
                'question_text' => 'What is a "stakeholder"?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'A person with an interest in a business',
                'options' => ['A person with an interest in a business', 'A type of food', 'A meeting room', 'A computer program'],
                'explanation' => 'A stakeholder is someone who has an interest in or is affected by a business.',
                'difficulty_level' => 3,
            ],
            [
                'question_text' => 'Fill in: "The project is _____ budget."',
                'question_type' => 'fill_blank',
                'correct_answer' => 'under',
                'options' => null,
                'explanation' => 'Under budget means spending less than planned.',
                'difficulty_level' => 3,
            ],
            [
                'question_text' => 'What does "follow up" mean in business?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'To check on the progress of something',
                'options' => ['To check on the progress of something', 'To walk behind someone', 'To copy someone', 'To finish work'],
                'explanation' => 'To follow up means to check on the progress or status of something.',
                'difficulty_level' => 3,
            ],
        ];

        foreach ($questions as $questionData) {
            $questionData['category_id'] = $category->id;
            Question::create($questionData);
        }
    }

    private function createAdvancedGrammarQuestions()
    {
        $category = Category::where('name', 'Advanced Grammar')->first();
        
        $questions = [
            [
                'question_text' => 'Which sentence uses the subjunctive mood correctly?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'If I were you, I would go.',
                'options' => ['If I were you, I would go.', 'If I was you, I would go.', 'If I am you, I would go.', 'If I be you, I would go.'],
                'explanation' => 'The subjunctive mood uses "were" instead of "was" for hypothetical situations.',
                'difficulty_level' => 4,
            ],
            [
                'question_text' => 'Complete: "By next year, I _____ for this company for 10 years."',
                'question_type' => 'fill_blank',
                'correct_answer' => 'will have been working',
                'options' => null,
                'explanation' => 'Future perfect continuous tense is used for actions that will be ongoing up to a future point.',
                'difficulty_level' => 4,
            ],
            [
                'question_text' => 'What is a "gerund"?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'A verb form ending in -ing used as a noun',
                'options' => ['A verb form ending in -ing used as a noun', 'A type of adjective', 'A past tense verb', 'A future tense verb'],
                'explanation' => 'A gerund is a verb form ending in -ing that functions as a noun.',
                'difficulty_level' => 4,
            ],
            [
                'question_text' => 'Fill in: "The book _____ by many students."',
                'question_type' => 'fill_blank',
                'correct_answer' => 'is being read',
                'options' => null,
                'explanation' => 'Present continuous passive voice is used here.',
                'difficulty_level' => 4,
            ],
            [
                'question_text' => 'Which sentence contains a relative clause?',
                'question_type' => 'multiple_choice',
                'correct_answer' => 'The man who lives next door is a doctor.',
                'options' => ['The man who lives next door is a doctor.', 'I went to the store.', 'She is reading a book.', 'They are playing football.'],
                'explanation' => 'A relative clause begins with a relative pronoun (who, which, that) and provides additional information.',
                'difficulty_level' => 4,
            ],
        ];

        foreach ($questions as $questionData) {
            $questionData['category_id'] = $category->id;
            Question::create($questionData);
        }
    }
}
