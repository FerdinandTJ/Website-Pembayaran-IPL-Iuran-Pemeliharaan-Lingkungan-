# Contributing to EnviroPay

Thank you for your interest in contributing to EnviroPay! This document provides guidelines and information for contributors.

## 🤝 Ways to Contribute

### 🐛 Bug Reports
- Use the [GitHub Issues](https://github.com/username/enviropay/issues) template
- Provide detailed reproduction steps
- Include environment information (OS, PHP version, browser)
- Attach screenshots or error logs when relevant

### 💡 Feature Requests
- Check existing issues first to avoid duplicates
- Describe the feature and its use case clearly
- Explain why this feature would benefit the community

### 🔧 Code Contributions
- Fork the repository
- Create a feature branch
- Write tests for new functionality
- Follow our coding standards
- Submit a pull request

### 📖 Documentation
- Improve README, API docs, or code comments
- Add examples or tutorials
- Translate documentation (coming soon)

## 🛠️ Development Setup

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL 8.0+

### Quick Start
```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/enviropay.git
cd enviropay

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
php artisan migrate --seed

# Build assets
npm run dev

# Start development server
php artisan serve
```

### Running Tests
```bash
# All tests
php artisan test

# With coverage
php artisan test --coverage

# Specific test
php artisan test --filter=PaymentTest
```

## 📋 Code Standards

### PHP Code Style
We follow [PSR-12](https://www.php-fig.org/psr/psr-12/) standards:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExampleController extends Controller
{
    /**
     * Handle the request.
     */
    public function handle(Request $request): Response
    {
        // Method body
    }
}
```

### Laravel Conventions
- Use Eloquent over Query Builder when possible
- Follow Laravel naming conventions for models, controllers, etc.
- Use form requests for validation
- Implement proper exception handling

### Frontend Standards
- Use Bootstrap 5 components and utilities
- Follow mobile-first responsive design
- Ensure accessibility (WCAG 2.1 AA)
- Optimize for performance

### Database
- Use descriptive migration names
- Add proper indexes for performance
- Include foreign key constraints
- Write seeders for test data

## 🔄 Git Workflow

### Branch Naming
```
feature/payment-gateway
bugfix/login-validation
hotfix/security-patch
docs/api-documentation
```

### Commit Messages
Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(auth): add two-factor authentication
fix(payment): resolve upload validation issue
docs(readme): update installation guide
style(ui): improve button spacing
refactor(controller): extract payment logic
test(unit): add user model tests
```

### Pull Request Process

1. **Update your fork**
   ```bash
   git checkout main
   git pull upstream main
   ```

2. **Create feature branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```

3. **Make changes and commit**
   ```bash
   git add .
   git commit -m "feat(feature): add amazing feature"
   ```

4. **Push to your fork**
   ```bash
   git push origin feature/amazing-feature
   ```

5. **Create Pull Request**
   - Use the PR template
   - Reference related issues
   - Add screenshots for UI changes
   - Ensure tests pass

## 🧪 Testing Guidelines

### Test Structure
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class PaymentTest extends TestCase
{
    public function test_user_can_upload_payment_proof(): void
    {
        // Arrange
        $user = User::factory()->create();
        
        // Act
        $response = $this->actingAs($user)
            ->post('/warga/upload-proof', [
                'invoice_id' => 1,
                'proof' => UploadedFile::fake()->image('proof.jpg')
            ]);
        
        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'invoice_id' => 1
        ]);
    }
}
```

### Coverage Requirements
- Minimum 80% code coverage for new features
- 100% coverage for critical payment logic
- Test both happy path and error scenarios

## 📚 Documentation

### Code Documentation
- Use PHPDoc for all public methods
- Include parameter and return type documentation
- Add usage examples for complex functions

```php
/**
 * Upload payment proof for an invoice.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  int  $invoiceId
 * @return \Illuminate\Http\RedirectResponse
 * 
 * @throws \Illuminate\Validation\ValidationException
 * 
 * @example
 * $controller->uploadProof($request, 123);
 */
public function uploadProof(Request $request, int $invoiceId): RedirectResponse
{
    // Implementation
}
```

### API Documentation
- Document all endpoints
- Include request/response examples
- Specify error codes and messages

## 🚫 What NOT to Contribute

- Breaking changes without discussion
- Code that doesn't follow our standards
- Features without tests
- Plagiarized or copyrighted code
- Malicious or insecure code

## 🏆 Recognition

Contributors will be:
- Listed in CONTRIBUTORS.md
- Mentioned in release notes
- Given credit in project documentation

## 📞 Getting Help

### Communication Channels
- **GitHub Issues**: Bug reports and feature requests
- **GitHub Discussions**: General questions and ideas
- **Email**: ferdinandtj4@gmail.com for private matters

### Mentorship
New contributors can request mentorship for:
- Understanding the codebase
- Laravel best practices
- Testing strategies
- Code review feedback

## 🎯 Current Priorities

### High Priority
- [ ] Email notification system
- [ ] Payment gateway integration
- [ ] Performance optimization
- [ ] Security enhancements

### Medium Priority
- [ ] Multi-language support
- [ ] Advanced reporting
- [ ] Mobile app (PWA)
- [ ] API versioning

### Low Priority
- [ ] Third-party integrations
- [ ] Advanced analytics
- [ ] Automated backups
- [ ] Load balancing

## 📄 Legal

By contributing to EnviroPay, you agree that:
- Your contributions are your original work
- You have the right to submit your contributions
- Your contributions are licensed under the MIT License
- You grant us the right to use your contributions

---

## 🙏 Thank You!

Every contribution, no matter how small, helps make EnviroPay better for everyone. We appreciate your time and effort in improving this project!

**Happy Coding! 🚀**
