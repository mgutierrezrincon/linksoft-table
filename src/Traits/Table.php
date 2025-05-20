<?php

namespace App\Traits;

use App\Models\File;
use App\Observers\InputValidationObserver;
use App\Observers\WithFileObserver;
use App\Observers\WithFilesObserver;
use App\Observers\WithImagesObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

trait Table
{
    /**
     * The attributes that are show in forms.
     *
     * @var array<int, string>
     */
    protected static $formFields = [];

    /**
     * The attributes that are show in forms.
     *
     * @var array<int, string>
     */
    protected static $formGroupedFields = [];

    /**
     * The attributes that are show in table.
     *
     * @var array<int, string>
     */
    protected static $tableColumns = [];

    /**
     * The type attributes that are show in table.
     *
     * @var array<int, string>
     */
    protected static $fieldTypes = [];

    /**
     * The type attributes that are show in table.
     *
     * @var array<int, string>
     */
    protected static $fieldWidths = [];

    /**
     * The type attributes that are show in table.
     *
     * @var array<int, string>
     */
    protected static $anotherFields = [];

    /**
     * The page size.
     *
     * @var int
     */
    protected static $tablePageSize = 10;

    /**
     * Hide add button.
     *
     * @var int
     */
    protected static $hideAddButton = false;

    /**
     * Hide edit button.
     *
     * @var int
     */
    protected static $hideEditButton = false;

    /**
     * Hide delete button.
     *
     * @var int
     */
    protected static $hideDeleteButton = false;

    /**
     * Hide view button.
     *
     * @var int
     */
    protected static $hideViewButton = false;
    /**
     * Read only fields.
     *
     * @var int
     */
    protected static $readOnlyFields = [];

    /**
     * Crud in modal.
     *
     * @var int
     */
    protected static $crudInModal = true;

    /**
     * With image.
     *
     * @var int
     */
    protected static $withImage = false;

    /**
     * With photo.
     *
     * @var int
     */
    protected static $withPhoto = false;

    /**
     * With images.
     *
     * @var int
     */
    protected static $withImages = false;

    /**
     * With file.
     *
     * @var int
     */
    protected static $withFile = false;

    /**
     * With files.
     *
     * @var int
     */
    protected static $withFiles = false;

    /**
     * No required fields.
     *
     * @var array<int, string>
     */
    protected static $noRequiredFields = [];

    protected static $modelNameAlt = '';

    /**
     * Init the attributes.
     *
     * @param mixed      $formFields
     * @param mixed      $tableColumns
     * @param null|mixed $tablePageSize
     * @param mixed      $readOnlyFields
     * @param mixed      $hideAddButton
     * @param mixed      $hideEditButton
     * @param mixed      $hideDeleteButton
     * @param mixed      $hideViewButton
     * @param mixed      $fieldTypes
     * @param mixed      $fieldWidths
     * @param mixed      $crudInModal
     * @param mixed      $withImage
     * @param mixed      $withPhoto
     * @param mixed      $anotherFields
     * @param mixed      $formGroupedFields
     * @param mixed      $noRequiredFields
     * @param mixed      $withFile
     * @param mixed      $withFiles
     * @param mixed      $withImages
     */
    public static function init($formFields = [], $tableColumns = [], $readOnlyFields = [], $hideAddButton = false, $hideEditButton = false, $hideDeleteButton = false, $hideViewButton = false, $tablePageSize = null, $fieldTypes = [], $fieldWidths = [], $crudInModal = true, $withImage = false, $withPhoto = false, $anotherFields = [], $formGroupedFields = [], $noRequiredFields = [], $withFile = false, $withFiles = false, $withImages = false, $modelNameAlt = '')
    {
        static::$modelNameAlt = $modelNameAlt;
        static::$formFields = $formFields;
        static::$tableColumns = $tableColumns;
        static::$readOnlyFields = $readOnlyFields;
        static::$tablePageSize = $tablePageSize ?? self::$tablePageSize;
        static::$hideAddButton = $hideAddButton;
        static::$hideEditButton = $hideEditButton;
        static::$hideDeleteButton = $hideDeleteButton;
        static::$hideViewButton = $hideViewButton;
        static::$fieldTypes = $fieldTypes;
        static::$fieldWidths = $fieldWidths;
        static::$crudInModal = $crudInModal;
        static::$withImage = $withImage;
        static::$withPhoto = $withPhoto;
        static::$anotherFields = $anotherFields;
        static::$formGroupedFields = $formGroupedFields;
        static::$noRequiredFields = $noRequiredFields;
        static::$withFile = $withFile;
        static::$withFiles = $withFiles;
        static::$withImages = $withImages;

        if (static::$withFile) {
            static::observe(WithFileObserver::class);
        }

        if (static::$withFiles) {
            static::observe(WithFilesObserver::class);
        }

        if (static::$withImages) {
            static::observe(WithImagesObserver::class);
        }

        static::observe(InputValidationObserver::class);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'parentable');
    }

    public function presets()
    {
    }

    /**
     * Get the attributes that are show in forms.
     *
     * @return array<int, string>
     */
    public static function getNoRequiredFields()
    {
        return static::$noRequiredFields;
    }

    /**
     * With file.
     *
     * @return bool
     */
    public static function getWithFile()
    {
        return static::$withFile;
    }

    /**
     * With images.
     *
     * @return bool
     */
    public static function getWithImages()
    {
        return static::$withImages;
    }

    /**
     * With files.
     *
     * @return bool
     */
    public static function getWithFiles()
    {
        return static::$withFiles;
    }

    /**
     * Get the attributes that are show in forms.
     *
     * @return array<int, string>
     */
    public static function getFields()
    {
        return static::$formFields;
    }

    /**
     * Get the attributes that are show in forms.
     *
     * @return array<int, string>
     */
    public static function getGroupedFields()
    {
        return static::$formGroupedFields;
    }

    /**
     * Get the attributes that are show in forms.
     *
     * @return array<int, string>
     */
    public static function getAnotherFields()
    {
        return static::$anotherFields;
    }

    /**
     * Get the attributes that are show in forms.
     *
     * @return array<int, string>
     */
    public static function getReadOnlyFields()
    {
        return static::$readOnlyFields;
    }

    /**
     * Get the attributes that are show in forms.
     *
     * @return array<int, string>
     */
    public static function getFieldTypes()
    {
        return static::$fieldTypes;
    }

    /**
     * Get the attributes that are show in forms.
     *
     * @return array<int, string>
     */
    public static function getFieldWidths()
    {
        return static::$fieldWidths;
    }

    /**
     * Get the attributes that are show in table.
     *
     * @return array<int, string>
     */
    public static function getColumns()
    {
        return static::$tableColumns;
    }

    /**
     * Get the page size.
     *
     * @return int
     */
    public static function getPageSize()
    {
        return static::$formFields;
    }

    /**
     * hideAddButton.
     *
     * @return bool
     */
    public static function getHideAddButton()
    {
        return static::$hideAddButton;
    }

    /**
     * hideEditButtond.
     *
     * @return bool
     */
    public static function getHideEditButton()
    {
        return static::$hideEditButton;
    }

    /**
     * hideDeleteButton.
     *
     * @return bool
     */
    public static function getHideDeleteButton()
    {
        return static::$hideDeleteButton;
    }

    /**
     * hideViewButton.
     *
     * @return bool
     */
    public static function getHideViewButton()
    {
        return static::$hideViewButton;
    }

    /**
     * Crud in modal.
     *
     * @return bool
     */
    public static function getCrudInModal()
    {
        return static::$crudInModal;
    }

    /**
     * With image.
     *
     * @return bool
     */
    public static function getWithImage()
    {
        return static::$withImage;
    }

    /**
     * With photo.
     *
     * @return bool
     */
    public static function getWithPhoto()
    {
        return static::$withPhoto;
    }

    public static function basicTableFilter()
    {
        return self::searchAndPaginate();
    }

    public static function getModelNameAlt()
    {
        return static::$modelNameAlt;
    }

    /**
     * Get the results of search.
     *
     * @param mixed      $conditions
     * @param mixed      $with
     * @param mixed      $order
     * @param mixed      $joins
     * @param null|mixed $searchFields
     * @param mixed      $table
     * @param mixed      $selectFields
     *
     * @return Collection
     */
    public static function searchAndPaginate($conditions = [], $with = [], $order = [['id', 'desc']], $joins = [], $searchFields = null, $table = '', $selectFields = '')
    {
        $results = self::with($with)->where(
            function ($query) use ($conditions, $searchFields) {
                $query->where(function ($query2) use ($searchFields) {
                    if ($s = Request::get('s')) {
                        foreach ($searchFields ?? static::$tableColumns as $column) {
                            $query2->orWhere($column, 'LIKE', '%'.$s.'%');
                        }
                    }
                });

                foreach ($conditions as $condition) {
                    if ('in' == strtolower($condition[1])) {
                        $query = $query->whereIn($condition[0], $condition[2]);
                    } else {
                        $query = $query->where($condition[0], $condition[1], $condition[2]);
                    }
                }

                foreach (Request::get('add_conditions') ?? [] as $condition) {
                    if (empty($condition[0]) || empty($condition[2])) {
                        continue;
                    }

                    if ('in' == strtolower($condition[1])) {
                        $query = $query->whereIn($condition[0], $condition[2]);
                    } else {
                        $query = $query->where($condition[0], $condition[1], $condition[2]);
                    }
                }
            }
        );

        if (!empty($table)) {
            $results = $results->select($table.'*');
        }

        if (!empty($selectFields)) {
            $results = $results->select($selectFields);
        }

        foreach ($joins as $join) {
            $results = $results->leftJoin($join[0], $join[1], $join[2], $join[3]);
        }

        foreach ($order as $s) {
            if (substr_count($s[0], '(') > 0) {
                $results = $results->orderBy(DB::raw($s[0]), $s[1]);
            } else {
                $results = $results->orderBy($table.$s[0], $s[1]);
            }
        }

        $results = $results->paginate(perPage: Request::has('page_size') ? Request::get('page_size') : static::$tablePageSize)
            ->withQueryString()
        ;

        return $results;
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
