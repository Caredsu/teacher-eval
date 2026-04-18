<?php
/**
 * Composer PSR-4 Autoloader Configuration
 * Add this to your composer.json "autoload" section if not present
 */

//  "autoload": {
//    "psr-4": {
//      "App\\": "src/"
//    }
//  }

// After updating composer.json, run: composer dump-autoload

// This file documents the namespace structure:

/*
App\
├── Core\
│   ├── Bootstrap.php       (IoC Container, dependency injection)
│   ├── Request.php         (HTTP request abstraction)
│   └── Response.php        (Standard HTTP responses)
│
├── Http\
│   ├── Controllers\        (Handle requests & responses)
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── TeacherController.php
│   │   ├── QuestionController.php
│   │   ├── EvaluationController.php
│   │   ├── ResultController.php
│   │   └── DashboardController.php
│   │
│   ├── Middleware\         (Intercept requests)
│   │   ├── AuthMiddleware.php
│   │   ├── RoleMiddleware.php
│   │   ├── ValidationMiddleware.php
│   │   └── ErrorHandler.php
│   │
│   └── Requests\           (Request validation)
│       ├── LoginRequest.php
│       ├── CreateUserRequest.php
│       ├── CreateTeacherRequest.php
│       └── SubmitEvaluationRequest.php
│
├── Services\               (Business logic)
│   ├── AuthService.php
│   ├── UserService.php
│   ├── TeacherService.php
│   ├── EvaluationService.php
│   └── ReportService.php
│
├── Repositories\           (Database queries)
│   ├── UserRepository.php
│   ├── TeacherRepository.php
│   ├── QuestionRepository.php
│   └── EvaluationRepository.php
│
├── Models\                 (Data objects)
│   ├── User.php
│   ├── Teacher.php
│   ├── Question.php
│   └── Evaluation.php
│
├── Validators\             (Input validation rules)
│   ├── UserValidator.php
│   ├── TeacherValidator.php
│   └── EvaluationValidator.php
│
├── Exceptions\             (Custom exceptions)
│   └── Exceptions.php
│
└── Config\                 (Configuration)
    ├── app.php
    ├── database.php
    └── auth.php
*/
?>
