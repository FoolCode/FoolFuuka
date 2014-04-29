<?php

namespace Foolz\Foolfuuka\Model;


class Data {

    /**
     * @param array $data
     * @return static $this
     */
    public function import($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    public function export()
    {
        $result = [];
        foreach ($this as $key => $value) {
                $result[$key] = $value;
        }

        return $result;
    }

}
