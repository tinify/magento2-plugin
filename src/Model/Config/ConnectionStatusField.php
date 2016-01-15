<?php

namespace Tinify\Magento\Model\Config;

use Tinify\Magento\Model\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ConnectionStatusField extends Field
{
    protected $status;

    public function __construct(Context $context, ConnectionStatus $status)
    {
        $this->status = $status;
        parent::__construct($context);
    }

    public function getElementHtml(AbstractElement $element)
    {
        $classes = ["tinify-connection-status"];

        switch ($this->status->getStatus()) {
            case ConnectionStatus::UNKNOWN:
                $element->setValue(__("Save configuration to check status."));
                break;
            case ConnectionStatus::SUCCESS:
                $classes[] = "tinify-success";
                $element->setValue(__("API connection successful."));
                $element->setComment(__(
                    "You have made %1 compressions this month.",
                    $this->status->getCompressionCount()
                ));
                break;
            case ConnectionStatus::FAILURE:
                $classes[] = "tinify-failure";
                $element->setValue(__("API connection unsuccessful."));
                $element->setComment(__(
                    "Last error: %1",
                    $this->status->getLastError()
                ));
                break;
        }

        return "<div class=\"" . implode(" ", $classes) . "\">{$element->getElementHtml()}</div>";
    }

    // @codingStandardsIgnoreLine - Magento violates underscore prefix rule.
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->getElementHtml($element);
    }
}
