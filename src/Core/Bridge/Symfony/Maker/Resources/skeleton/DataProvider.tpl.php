<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

<?php if ($generate_collection) { ?>
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
<?php } ?>
<?php if ($generate_item) { ?>
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
<?php } ?>
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
<?php if (null !== $resource_class) { ?>
use <?php echo $resource_full_class_name; ?>;
<?php } ?>

final class <?php echo $class_name; ?> implements <?php
if ($generate_collection) {
    echo 'ContextAwareCollectionDataProviderInterface, ';
}

if ($generate_item) {
    echo 'ItemDataProviderInterface, ';
}
?>RestrictedDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
<?php if (null !== $resource_class) { ?>
        return <?php echo $resource_class; ?>::class === $resourceClass; // Add your custom conditions here
<?php } else { ?>
        return false; // Add your custom conditions here
<?php } ?>
    }
<?php if ($generate_collection) { ?>

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        // Retrieve the collection from somewhere
    }
<?php } ?>
<?php if ($generate_item) { ?>

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])<?php
    if (null !== $resource_class) {
        echo ": ?$resource_class";
    } elseif (\PHP_VERSION_ID >= 70200) {
        echo ': ?object';
    }?>

    {
        // Retrieve the item from somewhere then return it or null if not found
    }
<?php } ?>
}
