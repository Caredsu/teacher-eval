// This file contains configuration examples
// Copy and modify as needed for your specific setup

/**
 * EXAMPLE 1: Local MongoDB (default)
 * 
 * MongoDB is running on localhost:27017
 * Database: teacher_evaluation
 */

// In config/database.php, this is the default:
// $mongoUri = 'mongodb://localhost:27017';
// $databaseName = 'teacher_evaluation';

/**
 * EXAMPLE 2: MongoDB Atlas (Cloud)
 * 
 * Using MongoDB Atlas cloud database
 */

// Set environment variable (or modify config/database.php):
// MONGO_URI=mongodb+srv://username:password@cluster.mongodb.net/?retryWrites=true&w=majority
// MONGO_DB=teacher_evaluation

/**
 * EXAMPLE 3: MongoDB with Authentication
 * 
 * Local MongoDB with username/password
 */

// MONGO_URI=mongodb://username:password@localhost:27017
// MONGO_DB=teacher_evaluation

/**
 * EXAMPLE 4: Docker MongoDB
 * 
 * Running MongoDB in Docker container
 */

// MONGO_URI=mongodb://mongo_container:27017
// MONGO_DB=teacher_evaluation

/**
 * CUSTOM SECURITY CONFIGURATION
 * 
 * To use custom JWT secret and adjust token expiration
 * Edit helpers.php:
 */

// Change token expiration (current: 24 hours)
// Line in generateToken(): 'exp' => time() + (24 * 60 * 60)
// Example for 7 days: 'exp' => time() + (7 * 24 * 60 * 60)

// Change JWT secret (current: 'teacher_evaluation_secret_key')
// Update both generateToken() and verifyToken() functions

/**
 * APACHE CONFIGURATION
 * 
 * Ensure your httpd-vhosts.conf has this:
 */

/*
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:\xampp\htdocs\teacher-eval"
    
    <Directory "C:\xampp\htdocs\teacher-eval">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
*/

/**
 * PRODUCTION RECOMMENDATIONS
 */

/*
1. Password Security:
   - Change default admin/staff passwords immediately
   - Use strong passwords (min 12 chars, mixed case, numbers, symbols)

2. Database Security:
   - Use MongoDB authentication
   - Restrict network access to MongoDB
   - Use connection string with credentials

3. API Security:
   - Enable HTTPS/SSL
   - Implement rate limiting
   - Add request logging/auditing
   - Use API keys instead of JWT for long-lived tokens

4. Environment Configuration:
   - Never commit .env with real credentials
   - Use environment variables for sensitive data
   - Different configs for dev/staging/production

5. Error Handling:
   - Disable verbose error messages in production
   - Log errors server-side
   - Return generic error messages to clients

6. CORS Policy:
   - Restrict origin in production
   - Change: header('Access-Control-Allow-Origin: *');
   - To: header('Access-Control-Allow-Origin: https://yourdomain.com');

7. Database Backups:
   - Regular backups of MongoDB data
   - Test restore procedures
   - Keep backups in secure location

8. Monitoring:
   - Monitor MongoDB performance
   - Track API response times
   - Alert on errors
   - Monitor disk space and memory usage
*/
