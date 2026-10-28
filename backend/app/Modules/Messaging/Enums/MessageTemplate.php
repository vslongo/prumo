<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Enums;

/**
 * Templates aprovados na Meta (categoria utility). O valor é o nome do template lá; os parâmetros,
 * na ordem do corpo, ficam em `messages.params`. Texto sugerido para cadastrar:
 * - quote_link: "Olá, {{1}}! A {{2}} enviou o orçamento do seu veículo. Para ver e aprovar: {{3}}"
 * - service_order_ready: "Olá, {{1}}! Seu veículo {{2}} está pronto para retirada na {{3}}."
 */
enum MessageTemplate: string
{
    case QuoteLink = 'quote_link';
    case ServiceOrderReady = 'service_order_ready';
}
