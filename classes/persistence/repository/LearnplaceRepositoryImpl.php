<?php
declare(strict_types=1);

namespace SRAG\Learnplaces\persistence\repository;

use arException;
use function array_map;
use ilDatabaseException;
use function is_null;
use SRAG\Learnplaces\persistence\dto\Learnplace;
use SRAG\Learnplaces\persistence\entity\Block;
use SRAG\Learnplaces\persistence\entity\PictureGalleryEntry;
use SRAG\Learnplaces\persistence\repository\exception\EntityNotFoundException;
use SRAG\Learnplaces\persistence\repository\util\BlockAccumulator;
use SRAG\Lernplaces\persistence\repository\LocationRepository;

/**
 * Class LearnplaceRepositoryImpl
 *
 * @package SRAG\Learnplaces\persistence\repository
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 */
class LearnplaceRepositoryImpl implements LearnplaceRepository {

	/**
	 * @var LocationRepository $locationRepository
	 */
	private $locationRepository;
	/**
	 * @var VisitJournalRepository $visitJournalRepository
	 */
	private $visitJournalRepository;
	/**
	 * @var ConfigurationRepository $configurationRepository
	 */
	private $configurationRepository;
	/**
	 * @var FeedbackRepository $feedbackRepository
	 */
	private $feedbackRepository;
	/**
	 * @var PictureRepository $pictureRepository
	 */
	private $pictureRepository;
	/**
	 * @var BlockAccumulator $blockAccumulator
	 */
	private $blockAccumulator;


	/**
	 * LearnplaceRepositoryImpl constructor.
	 *
	 * @param LocationRepository      $locationRepository
	 * @param VisitJournalRepository  $visitJournalRepository
	 * @param ConfigurationRepository $configurationRepository
	 * @param FeedbackRepository      $feedbackRepository
	 * @param PictureRepository       $pictureRepository
	 * @param BlockAccumulator        $blockAccumulator
	 */
	public function __construct(
		LocationRepository $locationRepository,
		VisitJournalRepository $visitJournalRepository,
		ConfigurationRepository $configurationRepository,
		FeedbackRepository $feedbackRepository,
		PictureRepository $pictureRepository,
		BlockAccumulator $blockAccumulator
	) {
		$this->locationRepository = $locationRepository;
		$this->visitJournalRepository = $visitJournalRepository;
		$this->configurationRepository = $configurationRepository;
		$this->feedbackRepository = $feedbackRepository;
		$this->pictureRepository = $pictureRepository;
		$this->blockAccumulator = $blockAccumulator;
	}

	/**
	 * @inheritdoc
	 */
	public function store(Learnplace $learnplace) : Learnplace {
		$activeRecord = $this->mapToEntity($learnplace);
		$activeRecord->store();
		return $this->mapToDTO($activeRecord);
	}

	/**
	 * @inheritdoc
	 */
	public function find(int $id): Learnplace {
		try {
			$learnplaceEntity = \SRAG\Learnplaces\persistence\entity\Learnplace::findOrFail($id);
			return $this->mapToDTO($learnplaceEntity);
		}
		catch (arException $ex) {
			throw new EntityNotFoundException("Learnplace with the id \"$id\" not found.", $ex);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function findByObjectId(int $id) : Learnplace {

		$learnplaceEntity = \SRAG\Learnplaces\persistence\entity\Learnplace::where(['fk_object_id' => $id])->first();
		if(is_null($learnplaceEntity))
			throw new EntityNotFoundException("Learnplace with the object id \"$id\" not found.");

		return $this->mapToDTO($learnplaceEntity);
	}

	/**
	 * @inheritdoc
	 */
	public function delete(int $id) {
		try {
			$learnplaceEntity = \SRAG\Learnplaces\persistence\entity\Learnplace::findOrFail($id);
			$learnplaceEntity->delete();
		}
		catch (arException $ex) {
			throw new EntityNotFoundException("Learnplace with id \"$id\" not found.", $ex);
		}
		catch(ilDatabaseException $ex) {
			throw new ilDatabaseException("Unable to delete learnplace with id \"$id\"");
		}
	}

	private function mapToDTO(\SRAG\Learnplaces\persistence\entity\Learnplace $learnplaceEntity) : Learnplace {

		$learnplace = new Learnplace();
		$learnplace
			->setId($learnplaceEntity->getPkId())
			->setConfiguration($this->configurationRepository->find($learnplaceEntity->getFkConfiguration()))
			->setVisitJournals($this->visitJournalRepository->findByLearnplaceId($learnplaceEntity->getPkId()))
			->setObjectId($learnplaceEntity->getFkObjectId())
			->setFeedback($this->feedbackRepository->findByLearnplaceId($learnplace->getId()));

		$learnplace
			->setPictures($this->fetchAllPicturesByLearnplaceId($learnplace->getId()))
			->setLocation($this->locationRepository->findByLearnplace($learnplace))
			->setFeedback($this->feedbackRepository->findByLearnplaceId($learnplace->getId()))
			->setBlocks($this->fetchAllBlocksByLearnplaceId($learnplace->getId()));

		return $learnplace;
	}

	private function mapToEntity(Learnplace $learnplace) : \SRAG\Learnplaces\persistence\entity\Learnplace {

		/**
		 * @var \SRAG\Learnplaces\persistence\entity\Learnplace $activeRecord
		 */
		$activeRecord = new \SRAG\Learnplaces\persistence\entity\Learnplace($learnplace->getId());

		$activeRecord
			->setFkConfiguration($learnplace->getConfiguration()->getId())
			->setFkObjectId($learnplace->getObjectId());

		return $activeRecord;
	}

	private function fetchAllPicturesByLearnplaceId(int $id) : array {
		//fetch pictures
		$galleryEntries = PictureGalleryEntry::where(['fk_learnplace_id' => $id])->get();

		$pictures = array_map(
			function (PictureGalleryEntry $entry) { return $this->pictureRepository->find($entry->getFkPictureId()); },
			$galleryEntries
		);

		return $pictures;
	}

	private function fetchAllBlocksByLearnplaceId(int $id) : array {
		$rawBlocks = Block::where(['fk_learnplace_id' => $id])->get();
		$blocks = array_map(
			function(Block $rawBlock) {return $this->blockAccumulator->fetchSpecificBlocksById($rawBlock->getPkId());},
			$rawBlocks
		);
		return $blocks;

	}
}