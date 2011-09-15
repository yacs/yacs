<?php
/**
 * a library to generate PDF files
 *
 * @see articles/fetch_as_pdf.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class PDF extends FPDF {

	/**
	 * encode a sequence of HTML tags, or plain text, to PDF
	 *
	 * @param string the text to append
	 * @return the content of PDF
	 * @see articles/fetch_as_pdf.php
	 */
	function encode($text) {
		global $context;

		//
		// meta information
		//

		// encode it to iso8859 -- sorry

		// document title
		if($context['page_title'])
			$this->SetTitle(utf8::to_iso8859(Safe::html_entity_decode($context['page_title'], ENT_COMPAT, 'ISO-8859-15')));

		// document author
		if($context['page_author'])
			$this->SetAuthor(utf8::to_iso8859(Safe::html_entity_decode($context['page_author'], ENT_COMPAT, 'ISO-8859-15')));

		// document subject
		if($context['subject'])
			$this->SetSubject(utf8::to_iso8859(Safe::html_entity_decode($context['subject'], ENT_COMPAT, 'ISO-8859-15')));

		// document creator (typically, the tool used to produce the document)
		$this->SetCreator('yacs');

		//
		// PDF content
		//

		// start the rendering engine
		$this->AliasNbPages();
		$this->AddPage();
		$this->SetFont('Arial','B', 16);

		// reference view.php instead of ourself to achieve correct links
		$text = str_replace('/fetch_as_pdf.php', '/view.php', $text);

		// remove all unsupported tags
		$text = strip_tags($text, "<a><b><blockquote><br><code><div><em><font><h1><h2><h3><h4><hr><i><img><li><p><pre><strong><table><tr><tt><u><ul>");

		// spaces instead of carriage returns
		$text = str_replace("\n", ' ', $text);

		// transcode to ISO8859-15 characters
		$text = utf8::to_iso8859(Safe::html_entity_decode($text, ENT_COMPAT, 'ISO-8859-15'));

		// locate every HTML/XML tag
		$areas = preg_split('/<(.*)>/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$height = 5;
		$link = '';
		foreach($areas as $index => $entity) {

			// a tag entity
			if($index % 2) {
				@list($tag, $attributes) = explode(' ', $entity, 2);

				switch(strtolower($tag)) {

				case 'a':
					if(preg_match('/href="(.*)"/i', $attributes, $matches)) {
						$link = $matches[1];

						// suppress local references (eg, in table of content)
						if(preg_match('/(#.*)/', $link))
							$link = '';

						// make URL out of URI
						elseif($link[0] == '/')
							$link = $context['url_to_home'].$link;
					}
					break;

				case 'b':
					$this->SetFont('', 'B');
					break;
				case '/b':
					$this->SetFont('', '');
					break;

				case 'blockquote';
					$this->Ln($height);
					break;
				case '/blockquote';
					$this->Ln($height);
					break;

				case 'br':
					$this->Ln($height);
					break;

				case 'code':
					$this->SetFont('Courier','',11);
					$this->SetFontSize(11);
					break;
				case '/code':
					$this->SetFont('Times','',12);
					$this->SetFontSize(12);
					break;

				case 'div':
				case '/div':
					$this->Ln($height);
					break;

				case 'em':
					$this->SetFont('', 'I');
					break;
				case '/em':
					$this->SetFont('', '');
					break;

				case 'font':
					if(preg_match('/color="#(.{6})"/i', $attributes, $matches)) {
						$color = $matches[1];

						$r = hexdec($color[0].$color[1]);
						$g = hexdec($color[2].$color[3]);
						$b = hexdec($color[4].$color[5]);

						$this->SetTextColor($r,$g,$b);
					}
					break;
				case 'font':
					$this->SetFont('Times','',12);
					$this->SetTextColor(0,0,0);
					$this->SetFontSize(12);
					break;

				case 'h1':
					$this->Ln(10);
					$this->SetTextColor(150,0,0);
					$this->SetFontSize(22);
					$height = 8;
					break;
				case 'h2':
					$this->Ln(8);
					$this->SetFontSize(18);
					$height = 6;
					break;
				case 'h3':
					$this->Ln(6);
					$this->SetFontSize(16);
					$height = 5;
					break;
				case 'h4':
					$this->Ln(6);
					$this->SetTextColor(102,0,0);
					$this->SetFontSize(14);
					$height = 5;
					break;
				case '/h1':
				case '/h2':
				case '/h3':
				case '/h4':
					$this->Ln($height);
					$this->SetFont('Times','',12);
					$this->SetTextColor(0,0,0);
					$this->SetFontSize(12);
					$height = 5;
					break;

				case 'hr':
					$this->Ln($height+2);
					$this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
					$this->Ln(3);
					break;

				case 'i':
					$this->SetFont('', 'I');
					break;
				case '/i':
					$this->SetFont('', '');
					break;

				case 'img':
					// only accept JPG and PNG
					if(preg_match('/src="([a-zA-Z0-9\/:_-]*\.(jpg|jpeg|png))"/i', $attributes, $matches)) {
						$image = $matches[1];

						// map on a file
						$image = preg_replace('/^'.preg_quote($context['url_to_home'].$context['url_to_root'], '/').'/', $context['path_to_root'], $image);

						// include the image only if the file exists
						if($attributes = Safe::GetImageSize($image)) {

							// insert an image at 72 dpi -- the k factor
							$this->Image($image, $this->GetX(), $this->GetY(), $attributes[0]/$this->k, $attributes[1]/$this->k);

							// make room for the image
							$this-> y += 3 + ($attributes[1]/$this->k);
						}
					}
					break;

				case 'li':
					$this->Ln($height);
					break;
				case '/li':
					break;

				case 'p':
				case '/p':
					$this->Ln($height);
					break;

				case 'pre':
					$this->SetFont('Courier','',11);
					$this->SetFontSize(11);
					$preformatted = TRUE;
					break;
				case '/pre':
					$this->SetFont('Times','',12);
					$this->SetFontSize(12);
					$preformatted = FALSE;
					break;

				case 'strong':
					$this->SetFont('', 'B');
					break;
				case '/strong':
					$this->SetFont('', '');
					break;

				case 'table':
					$this->Ln($height);
					break;
				case '/table':
					$this->Ln($height);
					break;

				case 'tr':
					$this->Ln($height+2);
					$this->Line($this->GetX(),$this->GetY(),$this->GetX()+187,$this->GetY());
					$this->Ln(3);
					break;

				case 'tt':
					$this->SetFont('Courier','',11);
					$this->SetFontSize(11);
					break;
				case '/tt':
					$this->SetFont('Times','',12);
					$this->SetFontSize(12);
					break;

				case 'u':
					$this->SetFont('', 'U');
					break;
				case '/u':
					$this->SetFont('', '');
					break;

				case 'ul':
					break;
				case '/ul':
					break;

				}


			// a textual entity
			} else {

				// we have to write a link
				if($link) {

					// a blue underlined link
					$this->SetTextColor(0,0,255);
					$this->SetFont('','U');
					$this->Write($height, $entity, $link);
					$link = '';
					$this->SetTextColor(0, 0, 0);
					$this->SetFont('', '');

				// regular text
				} else
					$this->Write($height, $entity);
			}

		}

		// return the PDF content as a string
		return $this->Output('dummy', 'S');

	}

	/**
	 * page footer
	 * @see included/fpdf.php
	 */
	function Footer() {
		global $context;

		// go to 1.5 cm from bottom
		$this->SetY(-15);

		// select Arial italic 8
		$this->SetFont('Times','',8);

		// print centered page number
		$this->SetTextColor(0,0,0);
		$this->Cell(0,4,'Page '.$this->PageNo().'/{nb}',0,1,'C');

		// we are proud of it
		$this->SetTextColor(0,0,180);
		$this->Cell(0, 4, utf8::to_iso8859(utf8::transcode(i18n::s('PDF export created by YACS'), TRUE)), 0, 0, 'C', 0, 'http://www.yacs.fr/');
	}

	/**
	 * page header
	 * @see included/fpdf.php
	 */
	function Header() {
		global $context;

		//Select Arial bold 15
		$this->SetTextColor(0,0,0);
		$this->SetFont('Times','',10);
		$this->Cell(0,10,utf8::to_iso8859($context['page_title']),0,0,'C');
		$this->Ln(4);
//		$this->Cell(0,10,$this->articleurl,0,0,'C');
		$this->Ln(7);
		$this->Line($this->GetX(), $this->GetY(), $this->GetX()+187, $this->GetY());

		//Line break
		$this->Ln(12);
		$this->SetFont('Times','',12);
	}
}


?>