<?php
namespace Emizentech\CategoryWidget\Block\Widget;

use Magento\Widget\Block\BlockInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class Showcategories extends \Magento\Framework\View\Element\Template implements BlockInterface
{

    protected $_template = 'widget/showcategories.phtml';
    protected $categoryRepository;
    protected $_categoryCollectionFactory;
    protected $_storeManager;
		protected $_registry;
		protected $_logger;

		protected $levelcount=1;

    public function __construct(Context $context, StoreManagerInterface $storeManager, CollectionFactory $categoryCollectionFactory, Registry $registry, LoggerInterface $logger)
    {

        $this->_storeManager = $storeManager;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
				$this->_registry = $registry;
				$this->_logger = $logger;
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
				if ($this->getData('useCurrentCategory') == 'yes')
				{
					$getCategory = $this->getCurrentCategory();
					if($getCategory)
					{
							return $getCategory->getId();
					} else {
							  $this->_logger->error('Category List Widget vertical: Current page is not a category page');
					}
				}
        if ($this->hasData('categoryids'))
				{
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
				/*
				$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

				$categoryFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
				$categories = $categoryFactory->create();
				$categories->addAttributeToSelect('*');
				$categories->addAttributeToFilter('level' , 2);
				$secondlevelcategory = $categories->getColumnValues('entity_id');
				*/
        // If this is the first invocation, we just want to iterate through the top level categories, otherwise fetch the children
        $children = $isFirst ? $categories : $category->getChildrenCategories();

				if (count($children)>0)
				{
					echo '<ul>';
					// For each category, fetch its children recursively

					foreach ($children as $child) {
									if (!$child->getIsActive()) {
										continue;
									}

									echo '<li class="level-item'.$this->levelcount.' submenu cat-level'.$child->getLevel().'"><a href="'.$child->getUrl($child).'">'.$child->getName().'</a></li>';
									if ($child->getChildren() && $level>1) { $this->levelcount++;
										$level--;
										$this->renderCategoriesTree($child,$child->getId(),$level);
										$this->levelcount--;
									}

					}
					echo '</ul>';
				}
		}

		/* $categoryId as category id */
    public function getCurrentCategory(){
        try {
            return $this->_registry->registry('current_category');
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
						$this->_logger->error('Category List Widget vertical: Category Not Found');
        }
    }
}