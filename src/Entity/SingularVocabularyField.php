<?php
/**
 * Created by PhpStorm.
 * User: bezalelhermoso
 * Date: 5/28/14
 * Time: 3:04 PM
 */

namespace ActiveLAMP\Taxonomy\Entity;

use ActiveLAMP\Bundle\TaxonomyBundle\Entity\Repository\EntityTermRepository;
use ActiveLAMP\Taxonomy\Model\EntityTermRepositoryInterface;
use ActiveLAMP\Taxonomy\Metadata\Entity;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork;


/**
 * @author Bez Hermoso <bez@activelamp.com>
 */
class SingularVocabularyField extends Term implements VocabularyFieldInterface
{
    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var VocabularyInterface
     */
    protected $vocabulary;

    /**
     * @var Term
     */
    protected $snapshot;

    /**
     * @var Entity
     */
    protected $metadata;

    /**
     * @var
     */
    protected $identifier;

    /**
     * @var EntityTerm
     */
    protected $entityTerm;

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @var bool
     */
    protected $dirty = false;

    /**
     * @var EntityTermRepositoryInterface
     */
    protected $entityTerms;

    /**
     * @param ObjectManager $em
     * @param EntityTermRepositoryInterface $entityTerms
     * @param VocabularyInterface $vocabulary
     * @param $entityType
     * @param $identifier
     * @param null $term
     */
    public function __construct(
        ObjectManager $em,
        EntityTermRepositoryInterface $entityTerms,
        VocabularyInterface $vocabulary,
        $entityType,
        $identifier,
        $term = null
    ) {
        $this->em = $em;
        $this->vocabulary = $vocabulary;
        $this->entityTerm = $term;
        $this->type = $entityType;
        $this->identifier = $identifier;
        $this->entityTerms = $entityTerms;
    }

    /**
     * @return array|EntityTerm[]
     */
    public function getInsertDiff()
    {
        $this->initialize();
        return $this->entityTerm === $this->snapshot || $this->entityTerm == null ? array() : array($this->entityTerm);
    }

    /**
     * @return array|EntityTerm[]
     */
    public function getDeleteDiff()
    {
        $this->initialize();
        return $this->entityTerm === $this->snapshot || $this->snapshot == null ? array() : array($this->snapshot);
    }

    /**
     * @return Vocabulary
     */
    public function getVocabulary()
    {
        return $this->vocabulary;
    }

    public function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $previous = null;


        if ($this->entityTerm !== null) {
            $previous = $this->entityTerm;
        }

        $entityTerm = $this->entityTerms->findEntity($this->vocabulary->getId(), $this->type, $this->identifier);

        $this->snapshot = $entityTerm;
        $this->entityTerm = $entityTerm;

        $this->initialized = true;

        if ($previous !== null) {
            $this->setTerm($previous);
        }

    }

    /**
     * @return Term
     */
    public function getTerm()
    {
        $this->initialize();
        return $this->entityTerm ? $this->entityTerm->getTerm() : null;
    }

    /**
     * @param $term
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setTerm($term = null)
    {

        $this->initialize();

        if (null === $term) {
            $this->entityTerm = null;
            return $this;
        }

        if (!$term instanceof Term) {
            throw new \InvalidArgumentException(sprintf(
                'Expected instance of Term. "%s" given.',
                get_class($term)
            ));
        }

        if ($this->em->getUnitOfWork()->getEntityState($term) == UnitOfWork::STATE_DETACHED) {
            $term = $this->em->merge($term);
        }

        if ($term->getVocabulary() !== $this->vocabulary) {
            throw new \InvalidArgumentException(sprintf(
                'Term "%s" (#%d) does not belong in "%s" vocabulary.',
                $term->getName(),
                $term->getId(),
                $this->vocabulary->getName()
            ));
        }

        if ($this->entityTerm && $this->entityTerm->getTerm() === $term) {
            return $this;
        }

        $className = $this->entityTerms->getClassName();

        $this->setDirty(true);
        $eTerm = new $className();
        /** @var $eTerm EntityTermInterface */
        $eTerm->setTerm($term);

        $this->entityTerm = $eTerm;

        return $this;
    }

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return (boolean) $this->initialized;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->getTerm() ? $this->getTerm()->getId() : null;
    }

    /**
     * @param string $name
     * @return $this|Term
     */
    public function setName($name)
    {
        if (!$this->getTerm()) {
            return $this;
        }

        $this->getTerm()->setName($name);
        return $this->getTerm();
    }

    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->getTerm() ? $this->getTerm()->getName() : null;
    }

    /**
     * @return null|string
     */
    public function getLabel()
    {
        return $this->getTerm() ? $this->getTerm()->getLabel() : null;
    }

    /**
     * @param int $weight
     * @return $this|Term
     */
    public function setWeight($weight)
    {
        if (!$this->getTerm()) {
            return $this;
        }

        $this->getTerm()->setWeight($weight);
        return $this->getTerm();
    }

    /**
     * @return int|null
     */
    public function getWeight()
    {
        return $this->getTerm() ? $this->getTerm()->getWeight() : null;
    }

    /**
     * @param VocabularyInterface $vocabulary
     * @return $this|Term|void
     */
    public function setVocabulary(VocabularyInterface $vocabulary = null)
    {
        if (!$this->getTerm()) {
            return $this;
        }

        $this->getTerm()->setVocabulary($vocabulary);
        return $this->getTerm();
    }


    /**
     * @return boolean
     */
    public function isDirty()
    {
        $this->initialize();
        return (boolean) $this->dirty;
    }

    /**
     * @param boolean $dirty
     * @return void
     */
    public function setDirty($dirty)
    {
        $this->dirty = $dirty;
    }
}