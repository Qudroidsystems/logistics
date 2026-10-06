<?php
namespace App\Traits;

use App\Models\Studentpicture;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait ImageManager {

    public function uploads($file, $path,$userid)
    {
        if($file) {
            $fileName   = $userid . '_'.$file->getClientOriginalName();
            //$avatarName = $filename.'_'.$userid.'_'.$file->extension();

               // /* Store $imageName name in DATABASE from HERE */
            USer::where("id", $userid)->update(["avatar" => $fileName]);
            Storage::disk('public')->put('images/staffavatar/' . $fileName, File::get($file));
            $file_name  = $file->getClientOriginalName();
            $file_type  = $file->getClientOriginalExtension();
            $filePath   = $path . $fileName;

            return $file = [
                'fileName' => $file_name,
                'fileType' => $file_type,
                'filePath' => $filePath,
                'fileSize' => $this->fileSize($file)
            ];
        }
    }

    public function studentuploads($file, $path,$userid)
    {
        if($file) {
            $fileName   = $userid . '_'.$file->getClientOriginalName();
            //$avatarName = $filename.'_'.$userid.'_'.$file->extension();

               // /* Store $imageName name in DATABASE from HERE */
            Studentpicture::where("studentid", $userid)->update(["picture" => $fileName]);
            Storage::disk('public')->put('images/studentavatar/' . $fileName, File::get($file));
            $file_name  = $file->getClientOriginalName();
            $file_type  = $file->getClientOriginalExtension();
            $filePath   = $path . $fileName;

            return $file = [
                'fileName' => $file_name,
                'fileType' => $file_type,
                'filePath' => $filePath,
                'fileSize' => $this->fileSize($file)
            ];
        }
    }
    public function fileSize($file, $precision = 2)
    {
        $size = $file->getSize();

        if ( $size > 0 ) {
            $size = (int) $size;
            $base = log($size) / log(1024);
            $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');
            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        }

        return $size;
    }
}

?>
