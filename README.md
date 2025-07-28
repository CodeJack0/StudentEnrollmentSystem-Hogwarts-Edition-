# StudentEnrollmentSystem-Hogwarts-Edition-

# 🧙‍♂️ Student Enrollment System – Hogwarts Edition

A web-based academic project designed for managing student enrollments in a magical-themed environment. Built with PHP, MySQL, and CSS, this system emphasizes **web security**, **role-based access control**, and full **CRUD functionality** for managing students, courses, and user accounts.

## 🚀 Features

- 🔐 **Secure User Authentication**
- 🛡️ **Role-Based Access Control (RBAC)**
  - Admins manage users and enrollment data
  - Staff manage student information
  - Students view their personal data
- 📄 **Full CRUD Operations**
  - Manage students, users, courses, and enrollments
- 🎨 **Responsive UI** with HTML & CSS
- 🔐 **Strong Web Security Practices**, including:
  - Session management with expiration
  - CSRF token protection on forms
  - Input sanitization & output encoding to prevent XSS
  - Account lockout after multiple failed login attempts

## 🛠️ Technologies Used

- **Backend**: PHP  
- **Database**: MySQL  
- **Frontend**: HTML, CSS  
- **Security Features**:
  - `password_hash()` and `password_verify()` for secure password storage
  - **CSRF Tokens** to protect form submissions
  - **XSS Protection** using output encoding (`htmlspecialchars`)
  - **Session Expiration** for inactive users
  - **Account Lockout** mechanism on repeated failed login attempts

## 💼 Project Purpose

This project was developed as an academic exercise to showcase:

- Secure web development practices
- Implementation of multi-role user access control
- Safe and structured CRUD operations
- Scalable back-end architecture with PHP & MySQL
