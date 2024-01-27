<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Paypal\Listener;

use Paypal\Paypal;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Mailer\MailerFactory;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SendEMail
 * @package IciRelais\Listener
 * @author Thelia <info@thelia.net>
 */
class SendConfirmationEmail extends BaseAction implements EventSubscriberInterface
{
    /**
     * @var MailerFactory
     */
    protected $mailer;

    protected $eventDispatcher;

    public function __construct(MailerFactory $mailer, EventDispatcherInterface $eventDispatcher)
    {
        $this->mailer = $mailer;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param OrderEvent $event
     *
     * @throws \Exception if the message cannot be loaded.
     */
    public function sendConfirmationEmail(OrderEvent $event)
    {
        if (Paypal::getConfigValue('send_confirmation_message_only_if_paid')) {
            // We send the order confirmation email only if the order is paid
            $order = $event->getOrder();

            if (!$order->isPaid() && $order->getPaymentModuleId() == Paypal::getModuleId()) {
                $event->stopPropagation();
            }
        }
    }

    /*
     * @params OrderEvent $order
     * Checks if order payment module is paypal and if order new status is paid, send an email to the customer.
     */
    public function updateStatus(OrderEvent $event)
    {
        $order = $event->getOrder();

        if ($order->isPaid() && $order->getPaymentModuleId() === Paypal::getModuleId()) {
            if (Paypal::getConfigValue('send_payment_confirmation_message')) {
                $this->mailer->sendEmailToCustomer(
                    Paypal::CONFIRMATION_MESSAGE_NAME,
                    $order->getCustomer(),
                    [
                        'order_id'  => $order->getId(),
                        'order_ref' => $order->getRef()
                    ]
                );
            }

            // Send confirmation email if required.
            if (Paypal::getConfigValue('send_confirmation_message_only_if_paid')) {
                //$event->getDispatcher()->dispatch($event, TheliaEvents::ORDER_SEND_CONFIRMATION_EMAIL);
                //$dispatcher->dispatch($event, TheliaEvents::ORDER_SEND_CONFIRMATION_EMAIL);
                $this->eventDispatcher->dispatch($event, TheliaEvents::ORDER_SEND_CONFIRMATION_EMAIL);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::ORDER_UPDATE_STATUS           => array("updateStatus", 100),
            TheliaEvents::ORDER_SEND_CONFIRMATION_EMAIL => array("sendConfirmationEmail", 129)
        );
    }
}
