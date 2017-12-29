<?php

namespace Drupal\select_or_other\Plugin\Validation;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ChoiceValidator;

/**
 * Validates the AllowedValues constraint.
 *
 * @codeCoverageIgnore
 * Ignore this code as it's a temporary workaround covered by integration tests.
 */
class AllowedValuesConstraintValidator extends ChoiceValidator implements ContainerInjectionInterface {

  use TypedDataAwareValidatorTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_user'));
  }

  /**
   * Constructs a new AllowedValuesConstraintValidator.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $typed_data = $this->getTypedData();

    if ($this->mustBeValidatedByCore($typed_data)) {
      $this->validateUsingCoreValidation($value, $constraint);
    }
    else {
      $constraint->choices = array_keys($this->getValidChoices($typed_data));
      $value = $this->getMainPropertyValue($typed_data);

      if (isset($value)) {
        parent::validate($value, $constraint);
      }
    }
  }

  private function mustBeValidatedByCore(TypedDataInterface $typed_data) {
    return !($typed_data instanceof ListItemBase) || $typed_data->getFieldDefinition()->getFieldStorageDefinition()->isBaseField();
  }

  private function validateUsingCoreValidation($value, Constraint $constraint) {
    $core_validator = new \Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraintValidator($this->currentUser);
    $core_validator->context = $this->context;
    $core_validator->validate($value, $constraint);
  }

  private function getValidChoices(TypedDataInterface $typed_data) {
    $allowed_options = [];
    if ($typed_data instanceof ListItemBase) {
      $field_name = $typed_data->getFieldDefinition()->getName();
      $entity_type = $typed_data->getFieldDefinition()->getTargetEntityTypeId();
      $field_storage_definition = FieldStorageConfig::loadByName($entity_type, $field_name);
      $allowed_options = options_allowed_values($field_storage_definition, $typed_data->getEntity());
    }
    return OptGroup::flattenOptions($allowed_options);
  }

  private function getMainPropertyValue($typed_data) {
    $name = $typed_data->getDataDefinition()->getMainPropertyName();
    if (!isset($name)) {
      throw new \LogicException('Cannot validate allowed values for complex data without a main property.');
    }
    return $typed_data->get($name)->getValue();
  }

}
