<?php
namespace App\Model;

class NewCampaignModel {

    private ?string $message = null;
    private bool $sendToAllReceipient = true;
    private ?string $recipients = null;

    /**
     * Get the value of message
     */ 
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set the value of message
     *
     * @return  self
     */ 
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the value of sendToAllReceipient
     */ 
    public function getSendToAllReceipient(): ?bool
    {
        return $this->sendToAllReceipient;
    }

    /**
     * Set the value of sendToAllReceipient
     *
     * @return  self
     */ 
    public function setSendToAllReceipient(bool $sendToAllReceipient)
    {
        $this->sendToAllReceipient = $sendToAllReceipient;

        return $this;
    }

    /**
     * Get the value of recipients
     *
     * @return  string|null
     */ 
    public function getRecipients(): ?string
    {
        return $this->recipients;
    }

    /**
     * Set the value of recipients
     *
     * @param  string|null  $recipients
     *
     * @return  self
     */ 
    public function setRecipients(?string $recipients)
    {
        $this->recipients = $recipients;

        return $this;
    }
}