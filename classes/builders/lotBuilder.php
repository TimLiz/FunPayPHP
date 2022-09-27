<?php

namespace lotBuilder;

use Exception;
use run\request;
use run\run;

class lotBuilder {
    /**
     * @var int Node id | Required
     * You can get me from url bar
     */
    public int $node_id;

    /**
     * @var string Type of lot(ex: Предметы)
     */
    public string $type;

    /**
     * @var string|null Lot name on Russian
     */
    public string|null $offerNameRu = null;

    /**
     * @var string|null Lot name on English
     */
    public string|null $offerNameEn = null;

    /**
     * @var string|null Lot description on Russian
     */
    public string|null $offerDescRu = null;

    /**
     * @var string|null Lot description on English
     */
    public string|null $offerDescEn = null;

    /**
     * @var int Lot price
     */
    public int $price = 1;

    /**
     * @var bool Is active
     */
    public bool $isActive = true;

    /**
     * @var int Amout
     */
    public int $amout = 1;

    /**
     * Saves the lot
     *
     * @return bool True on success
     * @throws Exception On error
     *
     * @TODO: Is required filled checks
     */
    public function save():bool {
        if ($this->isActive) {
            $active = "on";
        } else {
            $active = "off";
        }

        if ($this->offerNameRu == null && $this->offerNameEn == null) throw new Exception("You must set offerNameRu or offerNameEn");

        request::xhr("lots/offerSave", http_build_query(array(
            'csrf_token' => run::$runner->user->csrf,
            'offer_id' => 0,
            'node_id' => $this->node_id,
            'deleted' => null,
            'fields[type]' => $this->type,
            'fields[summary][ru]' => $this->offerNameRu,
            'fields[summary][en]' => $this->offerNameEn,
            'fields[desc][ru]' => $this->offerDescRu,
            'fields[desc][en]' => $this->offerDescEn,
            'price' => $this->price,
            'amout' => $this->amout,
            'active' => $active,
            'location' => "trade"
        )), run::$runner->user->session);

        return true;
    }
}