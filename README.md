# A-Pay

A-Pay is an innovative WhatsApp AI-powered payment platform that enables users to purchase airtime, data bundles, and pay electricity bills directly through WhatsApp. Built with Laravel, integrated with Paystack for secure payments, and backed by  MySQL database, A-Pay brings essential services to your fingertips.

## Features

- **WhatsApp AI Integration**: Interact with our intelligent AI assistant directly on WhatsApp to make purchases and payments
- **Airtime Purchase**: Buy airtime for all major networks instantly
- **Data Bundles**: Access flexible data bundle options for various needs
- **Electricity Bill Payments**: Pay your electricity bills seamlessly through the platform
- **Secure Payments**: Integrated with Paystack for safe and reliable transactions
- **User-Friendly Interface**: Simple, conversational AI makes purchasing easy and intuitive
- **Transaction History**: Track all your purchases and payments in one place
- **Real-Time Updates**: Instant confirmation of transactions via WhatsApp

## Tech Stack

- **Backend**: Laravel (PHP framework)
- **Payment Gateway**: Paystack
- **Database**: MySQL
- **Messaging**: Twilio
- **AI**: Custom WhatsApp AI assistant

## Requirements

- PHP 8.0 or higher
- Composer
- WhatsApp Business Account with API access

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/eniola229/a-pay.git
   cd a-pay
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Copy environment file**
   ```bash
   cp .env.example .env
   ```

4. **Configure environment variables**
   Edit `.env` and add your credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=apay
   DB_USERNAME=root
   DB_PASSWORD=your_password
   
   PAYSTACK_PUBLIC_KEY=your_paystack_public_key
   PAYSTACK_SECRET_KEY=your_paystack_secret_key
   
   TWILIO_SID=your_twilio_sid
   TWILIO_AUTH_TOKEN=your_twilio_auth_token
   TWILIO_NUMBER=your_twilio_number
   TWILIO_MESSAGING_SERVICE_SID=your_messaging_service_sid
   TWILIO_WEBHOOK_SECRET=your_webhook_secret
   TWILIO_W_NUMBER=your_whatsapp_number
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Run database migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed the database (optional)**
   ```bash
   php artisan db:seed
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

## Usage

### Getting Started with A-Pay on WhatsApp

1. Save A-Pay's WhatsApp number to your contacts
2. Send a message to start a conversation with our AI
3. The AI will ask for your name and email to create your account
4. After providing your details, you'll receive your unique bank transfer details
5. Fund your account by transferring money to the provided Wema Bank account
6. Once funded, simply tell the AI what you need (airtime, data, or electricity bill payment)
7. The AI automatically detects your network and processes your request instantly


## API Endpoints

### WhatsApp Webhook
- `POST /api/webhook/whatsapp` - Receive and process WhatsApp messages

### Payment
- `POST /api/payments/webhook` - Paystack webhook for payment confirmation
- `POST /api/payments/verify` - Verify payment status
- `GET /api/payments/history` - Get user payment history

### Services
- `GET /api/services/airtime` - Get airtime options
- `GET /api/services/data` - Get data bundle options
- `GET /api/services/electricity` - Get electricity providers

## Database Schema

### Key Tables

- **users**: Store user information and WhatsApp IDs
- **transactions**: Record all payment transactions
- **services**: Available services (airtime, data, electricity)
- **providers**: Service providers (networks, electricity companies)
- **wallet**: User wallet balance and transaction history

## Configuration

### Paystack Integration

Ensure your Paystack API keys are properly configured in the `.env` file. Test transactions are possible using Paystack's test keys.

### WhatsApp API Setup

1. Configure your WhatsApp Business Account webhook URL
2. Set up message templates for order confirmations
3. Configure message rate limiting and retry logic

## Security Considerations

- All transactions are encrypted and secured through Paystack
- User data is stored securely in the database
- API endpoints are protected with authentication middleware
- Rate limiting is implemented to prevent abuse
- Input validation is enforced on all user inputs

## Troubleshooting

**Issue**: Webhook not receiving messages
- Solution: Verify webhook URL is correctly configured in WhatsApp settings
- Ensure your server is publicly accessible

**Issue**: Payment not processing
- Solution: Check Paystack API keys are correct
- Verify internet connection and Paystack service status

**Issue**: Database connection error
- Solution: Ensure MySQL is running and credentials are correct
- Run `php artisan migrate` to set up tables

## Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/YourFeature`)
3. Commit your changes (`git commit -m 'Add YourFeature'`)
4. Push to the branch (`git push origin feature/YourFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support or inquiries, please reach out via:
- WhatsApp: 08035906313
- Email: support@apay.pay
- Issues: GitHub Issues

## Roadmap

- Voice message support
- Multi-language support
- Advanced analytics dashboard
- Subscription management
- Bill split and sharing features

---

**A-Pay** - Transactions Made Easy. 🚀