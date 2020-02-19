<?php
/**
 * Groundwork Image
 *
 * @author Julian Lankerd <julianlankerd@gmail.com>
 */

namespace Lankerd\GroundworkBundle\Services;

/**
 * This class is meant for file uploading
 */

class FileUpload
{
    public function saveFiles($files, $fileConfigs)
    {
        if(is_array($fileConfigs) && count($fileConfigs) > 0) {
          foreach ($fileConfigs as $key => $value) {
            if(is_array($value['name']) && count($value['name']) > 0 && array_key_exists($key, $files)) {
              foreach ($value['name'] as $count => $name) {
                $tempFile = '';
                if(array_key_exists($count, $files[$key])) {
                  $tempFile = $files[$key][$count];
                }
                if(is_file($tempFile) && $value['path'] != '' && $name != '') {
                  $tempFile->move(getcwd() . $value['path'], $name);
                }
              }
            }
          }
        }
        return true;
    }
}
