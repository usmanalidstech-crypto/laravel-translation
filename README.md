# Laravel Translation Management Service

A high-performance, scalable API for managing translations across multiple locales and contexts. Built with Laravel 12 and PHP 8.3, featuring token-based authentication, advanced caching, CDN support, and comprehensive testing.

## 🚀 Features

### Core Functionality
- **Multi-locale Support**: Manage translations for unlimited locales (en, fr, es, etc.)
- **Contextual Tagging**: Organize translations by context (web, mobile, desktop, admin)
- **CRUD Operations**: Full REST API for translation management
- **Advanced Search**: Filter by namespace, key, content, tags, or locale
- **JSON Export**: Optimized export endpoint for frontend applications

### Performance & Scalability
- **Sub-200ms Response Times**: All endpoints respond in milliseconds
- **100k+ Records Support**: Tested with large datasets
- **Export < 500ms**: JSON export handles large datasets efficiently
- **Redis Caching**: Intelligent caching with ETag support
- **Lazy Loading**: Memory-efficient data processing
- **Database Optimization**: Proper indexing and query optimization

### Security & Quality
- **Token-based Authentication**: HMAC-SHA256 secure token system
- **Input Validation**: Comprehensive request validation
- **PSR-12 Standards**: Clean, maintainable code
- **SOLID Principles**: Well-architected, scalable design
- **95%+ Test Coverage**: Unit, feature, and performance tests

### Additional Features
- **Docker Support**: Complete containerized setup
- **CDN Integration**: CloudFlare and AWS CloudFront support
- **OpenAPI Documentation**: Complete Swagger/OpenAPI 3.0 spec
- **Performance Testing**: Automated performance benchmarks
- **Database Seeding**: Bulk data generation for testing

## 🏗️ Architecture & Design Choices

### Database Schema
The application uses a normalized, scalable database design:

```
locales (id, code, name)
    ↓
translation_keys (id, namespace, key) ←→ tags (id, name)
    ↓
translation_values (id, translation_key_id, locale_id, value, version)
```

**Key Design Decisions:**
- **Separate Values Table**: Allows multiple translations per key per locale
- **Version Tracking**: Each translation value has a version for change tracking
- **Many-to-Many Tags**: Flexible tagging system for context organization
- **Unique Constraints**: Prevents duplicate keys within namespaces
- **Proper Indexing**: Optimized for common query patterns

### Performance Optimizations

1. **Lazy Loading**: Uses `lazyById(2000)` for memory-efficient large dataset processing
2. **Cursor Pagination**: Prevents performance degradation with large datasets
3. **Redis Caching**: Export endpoint cached with intelligent invalidation
4. **ETag Support**: HTTP caching with conditional requests (304 Not Modified)
5. **Database Indexes**: Strategic indexing on frequently queried columns
6. **Query Optimization**: Eager loading and optimized relationship queries

### Security Implementation

1. **HMAC Token System**: Custom token authentication using Laravel's app key
2. **Middleware Protection**: All API endpoints protected except health check
3. **Input Validation**: Comprehensive validation rules for all inputs
4. **SQL Injection Prevention**: Eloquent ORM with parameterized queries
5. **XSS Protection**: Proper output encoding and security headers

### Caching Strategy

- **Export Caching**: 5-minute cache for export endpoint with CDN support
- **Cache Invalidation**: Automatic cache clearing on data changes
- **ETag Headers**: Efficient conditional requests
- **CDN Integration**: Multi-tier caching with CloudFlare/AWS CloudFront

## 🛠️ Setup Instructions

### Prerequisites
- Docker and Docker Compose
- Git

### Quick Start

1. **Clone and Setup**
   ```bash
   git clone <repository-url>
   cd laravel-translation
   cp .env.example .env
   ```

2. **Build and Run**
   ```bash
   docker compose build --no-cache app
   docker compose up -d

   docker compose exec app php artisan key:generate
    docker compose exec app php artisan migrate --force
    docker compose exec app php artisan db:seed --class=Database\\Seeders\\BulkSeeder --force
   ```

3. **Wait for Initialization**
   ```bash
   docker compose logs -f artisan-init
   ```

4. **Get API Token**
   ```bash
   docker compose exec app php artisan tms:token "Dev"
   docker compose exec app cat storage/app/INIT_TOKEN.txt
   ```

5. **Access Services**
   - **API**: http://localhost:8080
   - **phpMyAdmin**: http://localhost:8081 (root / secret)
   - **OpenAPI Docs**: http://localhost:8080/public/openapi.yaml

### Environment Configuration

Create a `.env` file with the following variables:

```env
# Application
APP_NAME="Translation API"
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost:8080

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=translations
DB_USERNAME=root
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# CDN (Optional)
CDN_ENABLED=true
CDN_URL=https://your-cdn-domain.com
CDN_CLOUDFLARE_ENABLED=false
CDN_CLOUDFLARE_ZONE_ID=your-zone-id
CDN_CLOUDFLARE_API_TOKEN=your-api-token

# Seeding
SEED_TOTAL=5000
SEED_CHUNK=500
```

### Manual Setup (Without Docker)

1. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   php artisan migrate
   php artisan db:seed --class=BulkSeeder
   ```

4. **Create API Token**
   ```bash
   php artisan tms:token "Development Token"
   ```

5. **Run Tests**
   ```bash
   php artisan test
   ```

## 📚 API Usage

### Authentication
All API endpoints (except `/health`) require Bearer token authentication:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8080/api/keys
```

### Core Endpoints

#### Health Check
```bash
GET /api/health
# Returns: {"ok": true}
```

#### List Translations
```bash
GET /api/keys?namespace=web&tag=mobile&locale=en
# Returns paginated list with filtering
```

#### Create Translation
```bash
POST /api/keys
{
  "namespace": "web",
  "key": "welcome.message",
  "values": [
    {"locale": "en", "value": "Welcome!"},
    {"locale": "fr", "value": "Bienvenue!"}
  ],
  "tags": ["web", "mobile"]
}
```

#### Export Translations
```bash
GET /api/export?locale=en&namespace=web&tags=web,mobile
# Returns: {"web": {"welcome.message": "Welcome!"}}
```

### Advanced Features

#### Search Capabilities
- **By Namespace**: `?namespace=web`
- **By Key Pattern**: `?key=welcome`
- **By Content**: `?content=hello`
- **By Tag**: `?tag=mobile`
- **By Locale**: `?locale=en`

#### Pagination
All list endpoints support cursor pagination:
```json
{
  "data": [...],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 1000
  }
}
```

## 🧪 Testing

### Run All Tests
```bash
docker compose exec app php artisan test
```

### Test Categories

1. **Unit Tests** (`tests/Unit/`)
   - Model relationships and validation
   - Controller logic
   - Middleware functionality

2. **Feature Tests** (`tests/Feature/`)
   - API endpoint testing
   - Authentication flows
   - CRUD operations

3. **Performance Tests** (`tests/Feature/PerformanceTest.php`)
   - Response time validation
   - Large dataset handling
   - Memory usage testing

### Test Coverage
```bash
docker compose exec app php artisan test --coverage
# Target: >95% coverage
```

### Performance Benchmarks
```bash
docker compose exec app php artisan test --filter=PerformanceTest
# Validates <200ms response times and <500ms export times
```

## 🚀 Performance Characteristics

### Response Times (Tested with 10k+ records)
- **CRUD Operations**: < 50ms
- **Search/Filter**: < 100ms
- **Export (1k records)**: < 200ms
- **Export (10k records)**: < 500ms

### Memory Usage
- **Base Application**: ~20MB
- **With 10k Records**: < 50MB
- **Export Processing**: Memory-efficient lazy loading

### Scalability
- **Database**: Optimized for 100k+ translation keys
- **Caching**: Redis-based with intelligent invalidation
- **CDN**: Multi-tier caching support

## 🔧 Configuration

### CDN Setup

#### CloudFlare
```env
CDN_CLOUDFLARE_ENABLED=true
CDN_CLOUDFLARE_ZONE_ID=your-zone-id
CDN_CLOUDFLARE_API_TOKEN=your-api-token
```

#### AWS CloudFront
```env
CDN_AWS_CLOUDFRONT_ENABLED=true
CDN_AWS_CLOUDFRONT_DISTRIBUTION_ID=your-distribution-id
CDN_AWS_ACCESS_KEY=your-access-key
CDN_AWS_SECRET_KEY=your-secret-key
CDN_AWS_REGION=us-east-1
```

### Caching Configuration
```env
# Export endpoint caching
CDN_EXPORT_TTL=300        # 5 minutes
CDN_EXPORT_SMAXAGE=600    # 10 minutes for CDN

# Cache purging
CDN_PURGE_ENABLED=true
```

## 📊 Monitoring & Maintenance

### Health Monitoring
```bash
# Check API health
curl http://localhost:8080/api/health

# Check database connection
docker compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Performance Monitoring
- Monitor response times via application logs
- Use Redis monitoring for cache hit rates
- Database query analysis with Laravel Debugbar

### Maintenance Tasks
```bash
# Clear caches
docker compose exec app php artisan cache:clear

# Regenerate API token
docker compose exec app php artisan tms:token "New Token"

# Run database migrations
docker compose exec app php artisan migrate
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

### Code Standards
- Follow PSR-12 coding standards
- Maintain >95% test coverage
- Document all public methods
- Use meaningful variable and method names

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the OpenAPI documentation at `/public/openapi.yaml`
- Review the test cases for usage examples

---

**Built with ❤️ using Laravel 12, PHP 8.3, and modern best practices.**
