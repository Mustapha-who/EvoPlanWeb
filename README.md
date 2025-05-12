# EvoPlanWeb

A web-based scheduling and planning application for efficient project management, built for the Web Technologies 2A module.

## Description

EvoPlanWeb is a web application designed to streamline project scheduling and task management. It allows users to create, manage, and track project plans through an intuitive interface.

- **Objective**: Simplify project planning and improve team collaboration.
- **Problem Solved**: Addresses inefficiencies in manual scheduling and task tracking.
- **Main Features**:
  - Create and manage project schedules.
  - Assign tasks to team members.
  - Visualize timelines and progress.
  - Store data securely using MySQL.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Contributions](#contributions)
- [License](#license)

## Installation

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
   - Update database credentials in `src/config/database.php` (if applicable).

## Usage

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
   - Log in or register to start creating schedules and managing tasks.

## Contributions

We welcome contributions to EvoPlanWeb! Thank you to all who have helped improve this project.

### Contributors
- [MedAlizr](https://github.com/MedAlizr) - Responsible for implementing user functionality and core app integration.
- [Mehdi Ayachi](https://github.com/mehdi5255)- Responsible for the Event and Event Planning module and its core features.
- [Mustapha Jerbi](https://github.com/Mustapha-who) - Creator of the Workshop module and features.
- [Selim Ishak](https://github.com/selimisaac) - Creator of the Feedback and Claim Management module.
- [Ghalia el Ouaer](https://github.com/ghaliaelouaer24) - Responsible for the Resources module.
- [Mohamed Amine Mezlini](https://github.com/aminemezlini321) - Responsible for the Partnerships and Contracts module.

### How to Contribute?

1. **Fork the project**:
   - Go to the [EvoPlanWeb repository](https://github.com/MedAlizr/EvoPlanWeb) and click **Fork** to create a copy in your GitHub account.

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
   - Go to your fork on GitHub and create a pull request to the `main` branch of the original repository.

## License

This project is licensed under the **MIT License**. For more details, see the [LICENSE](LICENSE) file.
