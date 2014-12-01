<?php
	/*
	* This file is part of FireWind.
	* https://github.com/axilirator/firewind
	*
    * FireWind is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.

    * FireWind is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    * GNU General Public License for more details.

    * You should have received a copy of the GNU General Public License
    * along with FireWind. If not, see <http://www.gnu.org/licenses/>.
    */

	require_once __DIR__.'/phpmorphy/src/common.php';

	class morphyus {
		private $phpmorphy     = null;
		private $regexp_entity = '/&([a-zA-Z0-9]+);/';
		public  $regexp_word   = '/([a-zа-я0-9]+)/ui';

		function __construct() {
			$directory            = __DIR__.'/phpmorphy/dicts';
			$language             = 'ru_RU';
			$options[ 'storage' ] = PHPMORPHY_STORAGE_FILE;

			// Инициализация библиотеки //
			$this->phpmorphy      = new phpMorphy( $directory, $language, $options );
		}

		/**
		 * Разбивает текст на массив слов
		 *
		 * @param  {string} content Исходный текст для выделения слов
		 * @return {array}			Результирующий массив
		 */
		public function get_words( $content, $filter=true ) {
			// Фильтрация HTML-тегов и HTML-сущностей //
			if ( $filter ) {
				$content = strip_tags( $content );
				$content = preg_replace( $this->regexp_entity, ' ', $content );
			}

			// Перевод в верхний регистр //
			$content = mb_strtoupper( $content, 'UTF-8' );

			// Замена ё на е //
			$content = str_ireplace( 'Ё', 'Е', $content );

			// Выделение слов из контекста //
			preg_match_all( $this->regexp_word, $content, $words_src );
			return $words_src[ 1 ];
		}

		/**
		 * Находит леммы слова
		 * 
		 * @param {string} word   Исходное слово
		 * @param {array|boolean} Массив возможных лемм слова, либо false
		 */
		public function lemmatize( $word ) {
			// Получение базовой формы слова //
			$lemmas = $this->phpmorphy->lemmatize( $word );

			return $lemmas;
		}

		/**
		 * Оценивает значимость слова
		 * 
		 * @param  {string}  word    Исходное слово
		 * @param  {array}   profile Профиль текста
		 * @return {integer}         Оценка значимости от 0 до 5
		 */
		public function weigh( $word, $profile=false ) {
			// Попытка определения части речи //
			$partsOfSpeech = $this->phpmorphy->getPartOfSpeech( $word );

			// Профиль по умолчанию //
			if ( !$profile ) {
				$profile = [
					// Служебные части речи //
					'ПРЕДЛ' => 0,
					'СОЮЗ'  => 0,
					'МЕЖД'  => 0,
					'ВВОДН' => 0,
					'ЧАСТ'  => 0,
					'МС'    => 0,

					// Наиболее значимые части речи //
					'С'     => 5,
					'Г'     => 5,
					'П'     => 3,
					'Н'     => 3,

					// Остальные части речи //
					'DEFAULT' => 1
				];
			}

			// Если не удалось определить возможные части речи //
			if ( !$partsOfSpeech ) {
				return $profile[ 'DEFAULT' ];
			}

			// Определение ранга //
			for ( $i = 0; $i < count( $partsOfSpeech ); $i++ ) {
				if ( isset( $profile[ $partsOfSpeech[ $i ] ] ) ) {
					$range[] = $profile[ $partsOfSpeech[ $i ] ];
				} else {
					$range[] = $profile[ 'DEFAULT' ];
				}
			}

			return min( $range );
		}
	}
?>