<?php
namespace Emizentech\CategoryWidget\Block\Widget;

use Magento\Widget\Block\BlockInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryFactory;

class Showcategories extends \Magento\Framework\View\Element\Template implements BlockInterface
{

    protected $_template = 'widget/showcategories.phtml';
    protected $categoryRepository;
    protected $_categoryCollectionFactory;
    protected $_storeManager;

		/**
     * @var Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;


    public function __construct(Context $context, StoreManagerInterface $storeManager, CollectionFactory $categoryCollectionFactory, CategoryFactory $categoryFactory)
    {

        $this->_storeManager = $storeManager;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
				$this->_categoryFactory = $categoryFactory;
        parent::__construct($context);
    }

    /**
     * Get value of widgets' title parameter
     *
     * @return mixed|string
     */
    public function getTitle()
    {
        return $this->getData('title');
    }

    /**
     * Retrieve Category ids
     *
     * @return string
     */
    public function getCategoryIds()
    {
        if ($this->hasData('categoryids')) {
            return $this->getData('categoryids');
        }
        return $this->getData('categoryids');
    }

    /**
     *  Get the category collection based on the ids
     *
     * @return array
     */
    public function getCategoryCollection()
    {
        $category_ids = explode(",", $this->getCategoryIds());
        $condition = ['in' => array_values($category_ids)];

        $collection = $this->_categoryCollectionFactory->create()->addAttributeToFilter('entity_id', $condition)->addAttributeToSelect(['name', 'is_active', 'parent_id', 'image'])->setStoreId($this->_storeManager->getStore()->getId());
        return $collection;
    }

		public function getCategory($parentCatId, $level = false, $count = 0)
		{
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Call object manager from class object
				$categoryRepository = $objectManager->create('Magento\Catalog\Model\CategoryRepository');
				$parentcategories = $categoryRepository->get($parentCatId);
				$categories = $parentcategories->getChildrenCategories();
				$i=0;
				$ChildCategoryValue=array();
				foreach($categories as $category){
						if (!$category->getIsActive()) {
								continue;
						}
				    $ChildCategoryValue[$i] = ['label' => $category->getName(), 'value' => $category->getId(), 'url' =>$category->getUrl($category)];
						if ($count<$level) {
								$childCat = $this->getCategory($category->getId(),$level,$count++);

								if($childCat){
									 $ChildCategoryValue[$i]['child'] = $childCat;
								}

						}

						$i++;
				}

				return $ChildCategoryValue;

		}

		function renderCategoriesTree($category, $parentCatId, $level, $isFirst = false) {
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Call object manager from class object
				$categoryRepository = $objectManager->create('Magento\Catalog\Model\CategoryRepository');
				$parentcategories = $categoryRepository->get($parentCatId);
				$categories = $parentcategories->getChildrenCategories();

        // If this is the first invocation, we just want to iterate through the top level categories, otherwise fetch the children
        $children = $isFirst ? $categories : $category->getChildrenCategories();

        echo '<ul>';
        // For each category, fetch its children recursively
        foreach ($children as $child) {
								if (!$child->getIsActive()) {
									continue;
								}

								echo '<li><a href="'.$child->getUrl($child).'">'.$child->getName().'</a></li>';
								if ($child->getChildren() && $level>1) {
									$level--;
									$this->renderCategoriesTree($child,$child->getId(),$level);
								}
        }
        echo '</ul>';
		}
}