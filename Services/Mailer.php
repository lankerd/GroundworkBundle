<?php
/**
 * Groundwork Mailer
 * 
 * @author Julian Lankerd <julianlankerd@gmail.com>
 */

namespace Lankerd\GroundworkBundle\Services;

/**
 * This class is meant for any unique mailing requirements that
 * requires very basic messaging, therefore pull this service down
 * if you wish to send emails!
 */

class Mailer
{
    protected $mailer;

    /**
     * Mailer constructor.
     *
     * @param $mailer
     */
    public function __construct($mailer)
	{
		$this->mailer = $mailer;
	}

    /**
     * @param      $data
     * @param      $subject
     * @param      $fromAddr
     * @param      $fromName
     * @param null $to
     * @param null $cc
     *
     * @return bool|\Exception
     */
    public function sendComposedEmail($data, $subject, $fromAddr, $fromName, $to = null, $cc = null)
	{
		$message = $this->prepMessage($data ,$subject, $fromAddr, $fromName, $to, $cc);

		try
		{
			$this->sendEmail($message);
		}
		catch(\Exception $e)
		{
			return $e;
		}

		return true;
	}

    /**
     * @param      $data
     * @param      $subject
     * @param      $fromAddr
     * @param      $fromName
     * @param null $to
     * @param null $cc
     *
     * @return \Swift_Message
     */
    private function prepMessage($data, $subject, $fromAddr, $fromName, $to = null, $cc = null)
	{
		$message = \Swift_Message::newInstance()
			->setSubject($subject)
			->setFrom($fromAddr , $fromName);
		
		if(!is_array($to) && !empty($to))
		{
			$to = (array) $to;
		}
		
		if(!is_array($cc) && !empty($cc))
		{
			$cc = (array) $cc;
		}
		
		if(!empty($to))
		{
			foreach($to as $name => $addr)
			{
				if(!is_string($name))
				{
					$name = null;
				}

				if(!is_string($addr))
				{
					continue;
				}

				if(!filter_var($addr, FILTER_VALIDATE_EMAIL))
				{
					continue;
				}

				$message->addTo($addr, $name);
			}
		}
		
		if(!empty($cc))
		{
			foreach($cc as $name => $addr)
			{
				if(!is_string($name))
				{
					$name = null;
				}

				if(!is_string($addr))
				{
					continue;
				}

				if(!filter_var($addr, FILTER_VALIDATE_EMAIL))
				{
					continue;
				}

				$message->addCc($addr, $name);
			}
		}

		$message->setBody($data);
		return $message;
	}

    /**
     * @param \Swift_Message $message
     */
    protected function sendEmail(\Swift_Message $message)
	{
		$this->mailer->send($message);
	}
}
