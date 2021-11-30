<?php
/**
* @package     jelix
* @subpackage  forms
* @author      Laurent Jouanneau
* @contributor Julien Issler
* @copyright   2006-2018 Laurent Jouanneau
* @copyright   2009 Julien Issler
* @link        http://www.jelix.org
* @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

/**
 *
 * @package     jelix
 * @subpackage  forms
 */
class jFormsControlUpload extends jFormsControl {

    public $type='upload';

    /**
     * Used to verify the file mime type after the file was uploaded
     *
     * @var array list of possible mime types
     */
    public $mimetype=array();

    public $maxsize=0;

    /**
     * list of type mime or case-insensitive filename extension
     * or one of these types audio/*, video/*, or image/*
     *
     * All values should be separated by a comma
     *
     * This property is used to fill the accept HTML attribute
     *
     * @var string the content of the accept HTML attribute
     */
    public $accept = '';

    /**
     *
     * @var string the content of the capture HTML attribute
     */
    public $capture = '';

    public $fileInfo = array();


    protected $modified = false;

    function check(){
        if (isset($_FILES[$this->ref])) {
            $this->fileInfo = $_FILES[$this->ref];
        } else {
            $this->fileInfo = array('name' => '', 'type' => '', 'size' => 0, 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE);
        }

        if($this->fileInfo['error'] == UPLOAD_ERR_NO_FILE) {
            if($this->required)
                return $this->container->errors[$this->ref] = jForms::ERRDATA_REQUIRED;
        }else{
            if($this->fileInfo['error'] == UPLOAD_ERR_NO_TMP_DIR
               || $this->fileInfo['error'] == UPLOAD_ERR_CANT_WRITE)
                return $this->container->errors[$this->ref] = jForms::ERRDATA_FILE_UPLOAD_ERROR;

            if($this->fileInfo['error'] == UPLOAD_ERR_INI_SIZE
               || $this->fileInfo['error'] == UPLOAD_ERR_FORM_SIZE
               || ($this->maxsize && $this->fileInfo['size'] > $this->maxsize))
                return $this->container->errors[$this->ref] = jForms::ERRDATA_INVALID_FILE_SIZE;

            if($this->fileInfo['error'] == UPLOAD_ERR_PARTIAL
               || !is_uploaded_file($this->fileInfo['tmp_name']))
                return $this->container->errors[$this->ref] = jForms::ERRDATA_INVALID;

            if(count($this->mimetype)){
                $this->fileInfo['type'] = jFile::getMimeType($this->fileInfo['tmp_name']);
                if ( $this->fileInfo['type'] == 'application/octet-stream') {
                    // let's try with the name
                    $this->fileInfo['type'] = jFile::getMimeTypeFromFilename($this->fileInfo['name']);
                }

                if(!in_array($this->fileInfo['type'], $this->mimetype))
                    return $this->container->errors[$this->ref] = jForms::ERRDATA_INVALID_FILE_TYPE;
            }
        }
        return null;
    }

    function setValueFromRequest($request) {
        if(isset($_FILES[$this->ref])){
            $this->setData($_FILES[$this->ref]['name']);
            $this->modified = true;
        }else{
            $this->setData('');
        }
    }

    public function isModified()
    {
        if ($this->modified) {
            return true;
        }
        return parent::isModified();
    }

    function saveFile($directoryPath, $alternateName='') {

        if (!isset($_FILES[$this->ref]) || $_FILES[$this->ref]['error']!= UPLOAD_ERR_OK) {
            return false;
        }

        if ($this->maxsize && $_FILES[$this->ref]['size'] > $this->maxsize) {
            return false;
        }

        if ($alternateName == '') {
            $directoryPath .= $_FILES[$this->ref]['name'];
        } else {
            $directoryPath .= $alternateName;
        }
        return move_uploaded_file($_FILES[$this->ref]['tmp_name'], $directoryPath);
    }
}
