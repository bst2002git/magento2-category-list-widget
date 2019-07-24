<?php

namespace Emizentech\CategoryWidget\Model\Config\Source;

class YesNo implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'yes', 'label' => __('YES')],
            ['value' => 'no', 'label' => __('NO')]
				];
    }
}

