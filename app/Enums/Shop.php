<?php
/**
 * ショップ
 */

namespace App\Enums;

use App\Models\GamePackage;

enum Shop: int
{
    // ネット通販
    case Amazon = 1;
    case DMM = 2;
    case RAKUTEN_BOOKS = 3;
    case SURUGAYA = 4;

    // ゲーム配信サイト
    case Steam = 11;
    case PlayStationStore = 12;
    case MicrosoftStore = 13;
    case NintendoStore = 14;
    case NintendoEShop = 15;
    case DMM_GAMES = 16;
    case EGG = 17;
    case XboxStore = 18;
    case GOG = 19;
    case EPIC = 50;
    case BOOTH = 21;

    // スマホアプリ
    case APP_STORE = 31;
    case GooglePlay = 32;
    case SQM = 33;

    // アダルト
    case Getchu = 41;
    case DLsite = 42;
    case FANZA = 44;
    case FANZA_GAMES = 45;

    // 動画配信サービス
    case PRIME_VIDEO_SUBSCRIPTION = 51;
    case NETFLIX = 52;
    case DMM_TV = 53;
    case PRIME_VIDEO_BUY_RENTAL = 54;
    case RAKUTEN_TV = 56;
    case DISNEY_PLUS = 57;
    case PRIME_VIDEO_ALL = 58;

    // 電子書籍
    case KINDLE = 61;
    case DMM_BOOKS = 62;
    case FANZA_BOOKS = 63;

    // レンタル
    case DMM_RENTAL = 71;
    case FANZA_RENTAL = 72;

    // 〇〇で検索
    case Amazon_SEARCH = 101;
    case MERCARI_SEARCH = 102;
    case RAKUTEN_ICHIBA_SEARCH = 103;
    case SURUGAYA_SEARCH = 104;

    // 公式サイト
    case OFFICIAL_SITE = 201;

    /**
     * ショップ名
     *
     * @return string
     */
    public function name(): string
    {
        return match($this) {
            self::Amazon           => 'Amazon',
            self::DMM              => 'DMM通販',
            self::RAKUTEN_BOOKS    => '楽天ブックス',
            self::SURUGAYA         => '駿河屋',
            self::Steam            => 'Steam',
            self::PlayStationStore => 'PlayStation Store',
            self::MicrosoftStore   => 'Microsoft ストア',
            self::NintendoStore    => 'Nintendo Store',
            self::NintendoEShop    => 'Nintendo eShop',
            self::DMM_GAMES        => 'DMM GAMES',
            self::EGG              => 'EGG',
            self::XboxStore        => 'XBOX Game Store',
            self::GOG              => 'GOG.com',
            self::EPIC             => 'Epic Games Store',
            self::BOOTH            => 'BOOTH',
            self::APP_STORE        => 'App Store',
            self::GooglePlay       => 'Google Play',
            self::SQM              => 'スクエニマーケット',
            self::Getchu           => 'Getchu.com',
            self::DLsite           => 'DLsite',
            self::FANZA            => 'FANZA通販',
            self::FANZA_GAMES      => 'FANZA Games',
            self::PRIME_VIDEO_SUBSCRIPTION => 'Prime Video<br>見放題',
            self::NETFLIX          => 'Netflix',
            self::DMM_TV           => 'DMM TV',
            self::PRIME_VIDEO_BUY_RENTAL => 'Prime Video<br>購入/レンタル',
            self::PRIME_VIDEO_ALL  => 'Prime Video',
            self::RAKUTEN_TV       => '楽天TV',
            self::DISNEY_PLUS      => 'Disney+',
            self::KINDLE           => 'Kindle',
            self::DMM_BOOKS        => 'DMMブックス',
            self::DMM_RENTAL       => 'DMM宅配レンタル',
            self::FANZA_BOOKS      => 'FANZAブックス',
            self::FANZA_RENTAL     => 'FANZA宅配レンタル',
            self::Amazon_SEARCH    => 'Amazonで検索🔍',
            self::MERCARI_SEARCH   => 'メルカリで検索🔍',
            self::RAKUTEN_ICHIBA_SEARCH   => '楽天市場で検索🔍',
            self::SURUGAYA_SEARCH  => '駿河屋で検索🔍',
            self::OFFICIAL_SITE    => '公式サイト',
        };
    }

    /**
     * Font awesomeのマーク
     *
     * @return string
     */
    public function mark(): string
    {
        return match ($this) {
            self::Amazon           => '<i class="fab fa-amazon"></i>',
            self::Steam            => '<i class="fab fa-steam"></i>',
            self::PlayStationStore => '<i class="fab fa-playstation"></i>',
            self::APP_STORE        => '<i class="fab fa-apple"></i>',
            self::GooglePlay       => '<i class="fab fa-google-play"></i>',
            self::MicrosoftStore   => '<i class="fab fa-microsoft"></i>',
            self::NintendoStore, self::NintendoEShop => '<i class="fab fa-nintendo-switch"></i>',
            self::XboxStore        => '<i class="fab fa-xbox"></i>',
            default                => '',
        };
    }

    /**
     * input[type=select]に渡す用のリスト作成
     *
     * @param ?ProductDefaultImage $defaultImgType
     * @param string[] $excludeShopList
     * @return string[]
     */
    public static function selectList(?ProductDefaultImage $defaultImgType = null, array $excludeShopList = []): array
    {
        $result = [];

        $itemType = [
            ProductDefaultImage::GAME_DISTRIBUTION,
        ];
        if (in_array($defaultImgType, $itemType)) {
            $items = [
                self::Steam,
                self::PlayStationStore,
                self::XboxStore,
                self::NintendoStore,
                self::DMM_GAMES,
                self::FANZA_GAMES,
                self::EGG,
                self::GOG,
                self::EPIC,
                self::DLsite,
                self::OFFICIAL_SITE,
                self::MicrosoftStore,
                self::NintendoEShop,
                self::BOOTH,
            ];
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, 'ゲーム配信サイト', $item);
                }
            }
        }

        $itemType = [
            ProductDefaultImage::GAME_PACKAGE,
            ProductDefaultImage::DISC,
            ProductDefaultImage::BOOK,
            ProductDefaultImage::GAME_DISTRIBUTION,
        ];
        if (in_array($defaultImgType, $itemType)) {
            $items = [
                self::Amazon,
                self::DMM,
                self::RAKUTEN_BOOKS,
                self::SURUGAYA,
                self::FANZA,
                self::Getchu,
                self::BOOTH,
            ];
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, '通販', $item);
                }
            }
        }


        $itemType = [
            ProductDefaultImage::SMART_PHONE,
        ];
        if (in_array($defaultImgType, $itemType)) {
            $items = [
                self::APP_STORE,
                self::GooglePlay,
                self::SQM,
            ];
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, 'スマホアプリ', $item);
                }
            }
        }

        $itemType = [
            ProductDefaultImage::VIDEO_STREAMING,
        ];
        if (in_array($defaultImgType, $itemType)) {
            $items = [
                self::PRIME_VIDEO_ALL,
                self::PRIME_VIDEO_SUBSCRIPTION,
                self::PRIME_VIDEO_BUY_RENTAL,
                self::NETFLIX,
                self::DMM_TV,
                self::RAKUTEN_TV,
                self::DISNEY_PLUS,
            ];
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, '動画配信サービス', $item);
                }
            }
        }

        $itemType = [
            ProductDefaultImage::DIGITAL_BOOK,
        ];
        if (in_array($defaultImgType, $itemType)) {
            $items = [
                self::KINDLE,
                self::DMM_BOOKS,
                self::FANZA_BOOKS,
            ];
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, '電子書籍', $item);
                }
            }
        }


        $itemType = [
            ProductDefaultImage::RENTAL,
        ];
        if (in_array($defaultImgType, $itemType)) {
            $items = [
                self::DMM_RENTAL,
                self::FANZA_RENTAL,
            ];
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, 'レンタル', $item);
                }
            }
        }

        $itemType = [
            ProductDefaultImage::GAME_PACKAGE,
            ProductDefaultImage::DISC,
            ProductDefaultImage::BOOK,
            ProductDefaultImage::DIGITAL_BOOK,
            ProductDefaultImage::VIDEO_STREAMING,
            ProductDefaultImage::SEARCH,
        ];
        if (in_array($defaultImgType, $itemType)) {
            $items = [
                self::Amazon_SEARCH,
                self::MERCARI_SEARCH,
                self::RAKUTEN_ICHIBA_SEARCH,
                self::SURUGAYA_SEARCH,
            ];
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, '〇〇で検索', $item);
                }
            }
        }

        return $result;
    }

    /**
     * input[type=select]に渡す用のリスト作成
     *
     * @param GamePackage $pkg
     * @param string[] $excludeShopList
     * @return string[]
     */
    public static function selectListByPackage(GamePackage $pkg, array $excludeShopList = []): array
    {
        $result = [];

        $itemType = [
            ProductDefaultImage::GAME_DISTRIBUTION,
        ];
        if (in_array($pkg->default_img_type, $itemType)) {
            if ($pkg->platform->isSteam()) {
                $items = [
                    self::Steam,
                ];
            } else if ($pkg->platform->isSupportedOnPlayStationStore()) {
                $items = [
                    self::PlayStationStore,
                ];
            } else if ($pkg->platform->isSupportedOnXboxGameStore()) {
                $items = [
                    self::XboxStore,
                ];
            } else if ($pkg->platform->isSupportedOnMyNintendoStore()) {
                $items = [
                    self::NintendoStore,
                ];
            } else if ($pkg->platform->isGog()) {
                $items = [
                    self::GOG,
                ];
            } else if ($pkg->platform->isEgg()) {
                $items = [
                    self::EGG,
                ];
            } else if ($pkg->platform->isDMMGames()) {
                $items = [
                    self::DMM_GAMES,
                ];
            } else if ($pkg->platform->isFanzaGames()) {
                $items = [
                    self::FANZA_GAMES,
                ];
            } else if ($pkg->platform->isEpic()) {
                $items = [
                    self::EPIC,
                ];
            } else {
                $items = [
                    self::Steam,
                    self::PlayStationStore,
                    self::XboxStore,
                    self::NintendoStore,
                    self::DMM_GAMES,
                    self::FANZA_GAMES,
                    self::EGG,
                    self::GOG,
                    self::EPIC,
                    self::DLsite,
                    self::OFFICIAL_SITE,
                    self::MicrosoftStore,
                    self::NintendoEShop,
                    self::BOOTH,
                ];
            }


            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, 'ゲーム配信サイト', $item);
                }
            }
        }

        $itemType = [
            ProductDefaultImage::GAME_PACKAGE,
            ProductDefaultImage::DISC,
            ProductDefaultImage::BOOK,
            ProductDefaultImage::GAME_DISTRIBUTION,
        ];
        if (in_array($pkg->default_img_type, $itemType)) {
            if ($pkg->rating == Rating::R18A) {
                $items = [
                    self::Amazon,
                    self::FANZA,
                    self::RAKUTEN_BOOKS,
                    self::SURUGAYA,
                    self::Getchu,
                ];
            } else {
                $items = [
                    self::Amazon,
                    self::DMM,
                    self::NintendoStore,
                    self::RAKUTEN_BOOKS,
                    self::SURUGAYA,
                    self::BOOTH,
                ];
            }
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, '通販', $item);
                }
            }
        }


        $itemType = [
            ProductDefaultImage::SMART_PHONE,
        ];
        if (in_array($pkg->default_img_type, $itemType)) {
            $items = [
                self::APP_STORE,
                self::GooglePlay,
                self::SQM,
            ];
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, 'スマホアプリ', $item);
                }
            }
        }


        $itemType = [
            ProductDefaultImage::DISC,
            ProductDefaultImage::RENTAL,
        ];
        if (in_array($pkg->default_img_type, $itemType)) {
            $items = [
                self::DMM_RENTAL,
                self::FANZA_RENTAL,
            ];
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, 'レンタル', $item);
                }
            }
        }

        $itemType = [
            ProductDefaultImage::GAME_PACKAGE,
            ProductDefaultImage::DISC,
            ProductDefaultImage::BOOK,
            ProductDefaultImage::DIGITAL_BOOK,
            ProductDefaultImage::VIDEO_STREAMING,
            ProductDefaultImage::SEARCH,
        ];
        if (in_array($pkg->default_img_type, $itemType)) {
            $items = [
                self::Amazon_SEARCH,
                self::MERCARI_SEARCH,
                self::RAKUTEN_ICHIBA_SEARCH,
                self::SURUGAYA_SEARCH,
            ];
            foreach ($items as $item) {
                if (!in_array($item->value, $excludeShopList)) {
                    self::addItem($result, '〇〇で検索', $item);
                }
            }
        }

        return $result;
    }

    /**
     * 販売終了判定キーワード（HTML内にいずれかが含まれれば販売終了）
     *
     * @return string[]
     */
    public function soldOutKeywords(): array
    {
        return match($this) {
            self::Amazon           => [],
            self::DMM              => [],
            self::RAKUTEN_BOOKS    => [],
            self::SURUGAYA         => [],
            self::Steam            => [],
            self::PlayStationStore => [],
            self::MicrosoftStore   => [],
            self::NintendoStore    => [],
            self::NintendoEShop    => [],
            self::DMM_GAMES        => [],
            self::EGG              => [],
            self::XboxStore        => [],
            self::GOG              => [],
            self::EPIC             => [],
            self::BOOTH            => [],
            self::APP_STORE        => [],
            self::GooglePlay       => [],
            self::SQM              => [],
            self::Getchu           => [],
            self::DLsite           => [],
            self::FANZA            => [],
            self::FANZA_GAMES      => [],
            self::PRIME_VIDEO_SUBSCRIPTION => [],
            self::NETFLIX          => [],
            self::DMM_TV           => [],
            self::PRIME_VIDEO_BUY_RENTAL => [],
            self::PRIME_VIDEO_ALL  => [],
            self::RAKUTEN_TV       => [],
            self::DISNEY_PLUS      => [],
            self::KINDLE           => [],
            self::DMM_BOOKS        => [],
            self::DMM_RENTAL       => [],
            self::FANZA_BOOKS      => [],
            self::FANZA_RENTAL     => [],
            self::Amazon_SEARCH    => [],
            self::MERCARI_SEARCH   => [],
            self::RAKUTEN_ICHIBA_SEARCH => [],
            self::SURUGAYA_SEARCH  => [],
            self::OFFICIAL_SITE    => [],
        };
    }

    /**
     * 年齢確認ページの判定キーワード（HTML内にいずれかが含まれれば販売中と判断する）
     *
     * @return string[]
     */
    public function ageGateKeywords(): array
    {
        return match($this) {
            self::Amazon           => ['年齢確認', '警告', '歳以上ですか'],
            self::DMM              => [],
            self::FANZA            => ['歳以上ですか'],
            self::FANZA_GAMES      => ['歳以上ですか'],
            self::DLsite           => [],
            self::Getchu           => [],
            self::NintendoStore    => ['歳以上ですか'],
            default                => [],
        };
    }

    /**
     * URLチェックの対象にするか
     * 検索系ショップはリンクが汎用URLのためスキップする
     */
    public function isCheckable(): bool
    {
        return match($this) {
            self::Amazon_SEARCH,
            self::MERCARI_SEARCH,
            self::RAKUTEN_ICHIBA_SEARCH,
            self::SURUGAYA_SEARCH => false,
            default               => true,
        };
    }

    /**
     * ショップを追加
     *
     * @param array $result
     * @param string $category
     * @param Shop $shop
     * @return void
     */
    private static function addItem(array &$result, string $category, Shop $shop): void
    {
        $result[$category][$shop->value] = $shop->name();
    }
}
