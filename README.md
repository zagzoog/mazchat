# AI Chat Application

A modern chat application with AI capabilities, built with PHP and JavaScript.

## Features

- Real-time chat interface
- AI-powered responses
- Conversation management
- User authentication
- Admin dashboard
- Usage statistics tracking
- Responsive design
- RTL support for Arabic

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/ai-chat.git
```

2. Create a MySQL database and import the schema:
```bash
mysql -u your_username -p your_database < database/schema.sql
```

3. Copy the configuration files:
```bash
cp config.example.php config.php
cp db_config.example.php db_config.php
```

4. Update the configuration files with your database credentials and other settings.

5. Set up your web server to point to the project directory.

## Configuration

1. Edit `config.php` to set your application settings
2. Edit `db_config.php` to configure your database connection

## Usage

1. Access the application through your web browser
2. Create an account or log in
3. Start chatting with the AI assistant

## Development

The project uses:
- PHP for backend
- JavaScript for frontend
- MySQL for database
- Bootstrap for styling

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request 