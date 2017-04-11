<?php

declare(strict_types=1);

namespace NAttreid\Cms\Model;

use NAttreid\Cms\Model\Configuration\ConfigurationRepository;
use NAttreid\Cms\Model\Locale\LocalesRepository;
use Nextras\Orm\Model\Model;

/**
 * @property-read ConfigurationRepository $configuration
 * @property-read LocalesRepository $locales
 *
 * @author Attreid <attreid@gmail.com>
 */
class Orm extends Model
{

}
