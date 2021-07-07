<?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\DataPersister\ResumableDataPersisterInterface;
<?php if (null !== $resource_class) { ?>
use <?php echo $resource_full_class_name; ?>;
<?php } ?>

final class <?php echo $class_name; ?> implements ContextAwareDataPersisterInterface, ResumableDataPersisterInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
<?php if (null !== $resource_class) { ?>
        return $data instanceof <?php echo $resource_class; ?>::class; // Add your custom conditions here
<?php } else { ?>
        return false; // Add your custom conditions here
<?php } ?>
    }

    /**
     * {@inheritdoc}
     */
    public function resumable(array $context = []): bool
    {
        return false; // Set it to true if you want to call the other data persisters
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data, array $context = [])<?php
   if (null !== $resource_class) {
       echo ": $resource_class";
   } elseif (\PHP_VERSION_ID >= 70200) {
       echo ': object';
   }?>

    {
        // Call your persistence layer to save $data

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data, array $context = []): void
    {
        // Call your persistence layer to delete $data
    }
}
