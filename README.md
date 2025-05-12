# EvoPlan: Healthcare Event Management Web (EvoPlanWeb) 📋

![PHP](https://img.shields.io/badge/PHP-8.x-blue)
![License](https://img.shields.io/badge/License-MIT-blue)
![Esprit](https://img.shields.io/badge/Esprit%20School-Web%20Technologies%202A-orange)

A web-based frontend for the EvoPlan healthcare event management platform, developed at Esprit School of Engineering.

---

## Overview 🌟

EvoPlanWeb is the web frontend for the EvoPlan project, created as part of the **Web Technologies 2A** course at **[Esprit School of Engineering](https://esprit.tn/)**. This platform enables healthcare professionals and organizers to manage events like workshops, conferences, and training sessions through an intuitive web interface. Built with **PHP**, **HTML**, **CSS**, **JavaScript**, and **MySQL**, it integrates with the Hexatech Java backend to provide a seamless event management experience.

---

## Description 📝

EvoPlanWeb serves as the user-facing interface for the EvoPlan healthcare event management platform. It allows users to create, manage, and track healthcare events, ensuring efficient planning and collaboration.

- **Objective**: Simplify healthcare event planning with an intuitive web interface.
- **Problem Solved**: Streamlines event management for healthcare professionals with a user-friendly frontend.
- **Main Features**:
  - Create and manage healthcare events (e.g., workshops, conferences).
  - Assign tasks and resources for event planning.
  - View event timelines and progress.
  - Submit feedback and manage claims.

---

## Table of Contents 📑

- [Overview](#overview)
- [Description](#description)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Usage](#usage)
- [Contributions](#contributions)
- [Acknowledgements](#acknowledgements)
- [License](#license)

---

## Tech Stack 🛠️

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP 8.x
- **Database**: MySQL
- **Development Environment**: WAMP/XAMPP
- **Other Tools**: Git

---

## Installation ⚙️

Follow these steps to set up EvoPlanWeb locally:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/MedAlizr/EvoPlanWeb.git
   cd EvoPlanWeb
   ```

2. **Set up WAMP/XAMPP**:
   - Place the project folder in the `www` (WAMP) or `htdocs` (XAMPP) directory.
   - Start Apache and MySQL from the WAMP/XAMPP interface.
   - Access the project via `http://localhost/EvoPlanWeb`.

3. **Configure the database**:
   - Create a MySQL database (e.g., `evoplanweb_db`).
   - Import the SQL schema from `src/database/schema.sql` (if available).
   - Update database credentials in `src/config/database.php`:
     ```php
     <?php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'evoplanweb_db');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     ?>
     ```

---

## Usage 🚀

To use EvoPlanWeb:

1. **Install PHP**:
   - For Windows, use WAMP/XAMPP as described in the installation section.
   - For Linux (e.g., Ubuntu), install PHP and MySQL:
     ```bash
     sudo apt update
     sudo apt install php php-mysql mysql-server
     ```

2. **Verify PHP installation**:
   ```bash
   php -v
   ```

3. **Run the application**:
   - Ensure Apache and MySQL are running.
   - Open `http://localhost/EvoPlanWeb` in a browser.
   - Log in or register to start managing healthcare events, such as creating workshops or tracking conference schedules.

---

## Contributions 🤝

We welcome contributions to EvoPlanWeb! Thank you to all who have helped improve this project.

### Contributors
- [MedAlizr](https://github.com/MedAlizr) - Responsible for implementing user functionality and core app integration.
- [Mehdi Ayachi](https://github.com/mehdi5255) - Responsible for the Event and Event Planning module and its core features.
- [Mustapha Jerbi](https://github.com/Mustapha-who) - Creator of the Workshop module and features.
- [Selim Ishak](https://github.com/selimisaac) - Creator of the Feedback and Claim Management module.
- [Ghalia El Ouaer](https://github.com/ghaliaelouaer24) - Responsible for the Resources module.
- [Mohamed Amine Mezlini](https://github.com/aminemezlini321) - Responsible for the Partnerships and Contracts module.

### How to Contribute?

1. **Fork the project**:
   - Go to the [EvoPlanWeb repository](https://github.com/MedAlizr/EvoPlanWeb) and click **Fork**.

2. **Clone your fork**:
   ```bash
   git clone https://github.com/your-username/EvoPlanWeb.git
   cd EvoPlanWeb
   ```

3. **Create a new branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

4. **Make changes and commit**:
   ```bash
   git add .
   git commit -m "Add your feature or fix"
   git push origin feature/your-feature-name
   ```

5. **Submit a pull request**:
   - Create a pull request to the `main` branch of the original repository.

---

## Acknowledgements 🙏

This project was developed as part of the **Web Technologies 2A** course at **Esprit School of Engineering**. We thank our instructors and peers for their guidance and support in building this web frontend for EvoPlan.

---

## License 📜

This project is licensed under the **MIT License**. For more details, see the [LICENSE](LICENSE) file.
