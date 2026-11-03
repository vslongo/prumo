<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserInvitation;
use App\Modules\Auth\Policies\UserInvitationPolicy;
use App\Modules\Auth\Policies\UserPolicy;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Policies\CustomerPolicy;
use App\Modules\Media\Events\MediaUploaded;
use App\Modules\Media\Listeners\GenerateThumbnailOnUpload;
use App\Modules\Messaging\Listeners\QueueQuoteLinkMessage;
use App\Modules\Messaging\Listeners\QueueServiceOrderReadyMessage;
use App\Modules\Quotes\Events\QuoteApproved;
use App\Modules\Quotes\Events\QuoteSent;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Policies\QuotePolicy;
use App\Modules\ServiceOrders\Events\ServiceOrderStatusChanged;
use App\Modules\ServiceOrders\Listeners\CreateServiceOrderFromApprovedQuote;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Policies\ServiceOrderPolicy;
use App\Modules\ServiceTypes\Models\ServiceType;
use App\Modules\ServiceTypes\Policies\ServiceTypePolicy;
use App\Modules\Tenancy\TenantContext;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Policies\VehiclePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(UserInvitation::class, UserInvitationPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(ServiceType::class, ServiceTypePolicy::class);
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::policy(ServiceOrder::class, ServiceOrderPolicy::class);

        Event::listen(QuoteApproved::class, CreateServiceOrderFromApprovedQuote::class);
        Event::listen(QuoteSent::class, QueueQuoteLinkMessage::class);
        Event::listen(ServiceOrderStatusChanged::class, QueueServiceOrderReadyMessage::class);
        Event::listen(MediaUploaded::class, GenerateThumbnailOnUpload::class);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
        // Cada cadastro cria um tenant: limite apertado por IP contra criação em massa.
        RateLimiter::for('signup', fn (Request $request) => Limit::perHour(5)->by((string) $request->ip()));
        RateLimiter::for('public-links', fn (Request $request) => Limit::perMinute(30)->by((string) $request->ip()));
        // Por link (não por IP): cada pedido cria um registro em media e uma URL de upload.
        RateLimiter::for('quote-signature', fn (Request $request) => Limit::perHour(10)->by(hash('sha256', (string) $request->route('token'))));
    }
}
