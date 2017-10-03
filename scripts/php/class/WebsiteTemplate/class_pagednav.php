<?php

class PagedNav extends Website {
	private $NumRecPerPage;			// number of records per page, used to calculate the number of pages in navigation
	private $NumRec;						// total number of records with current query, used to calculate number of pages
	private $CurPageNum;				// Current page to display.
	private $Range = 6;				// range of pages in paged navigation, must be an even number
	private $StepSmall = 10;		// [-10] [+10] backward - forward jump, must be an even number
	private $StepBig = 50;			// [-50] [+50] backward - forward jump, must be an even number
	private $Lan = "D";					// language of text navigation
	private $VarNamePgNav = 'PgNav';	// name of variable in querystring to set page number
	private $VarNameNumRecPP = 'NumRecPp';	// name of variable in querystringnumber to set # records per page
	private $FormMethod = 'GET';	// default method is GET, e.g use querystring
		/**	 * Constructs the paged navigation.	 * 	 * Total number of records with given query, e.g. with WHERE clause included.	 * 	 * @param integer $CurPageNum Current page to display.	 * @param integer $NumRec Total number of records	 * @param integer $NumRecPerPage Number of records per page	 */
	public function __construct($CurPageNum, $NumRec, $NumRecPerPage) {
		parent::__construct();
		$this->CurPageNum = $CurPageNum;
		$this->NumRec = $NumRec;
		$this->NumRecPerPage = $NumRecPerPage;
	}
	
	public function SetMethod($Method) {
		$this->FormMethod = $Method;
	}
		/**	 * Set the number of links to directly accessible pages.	 * 	 * This number has to be even.	 * 	 * @param integer $Range number of links	 */
	public function SetRange($Range) { 
		// TODO: check if even number
		$this->Range = $Range;
	}
		/**	 * Set how many pages can be skipped.	 *	 * @param integer $StepSmall	 * @param integer $StepBig	 */
	public function SetStep($StepSmall, $StepBig) {
		// TODO: check if even number
		$this->StepSmall = $StepSmall;
		$this->StepBig = $StepBig;
	}
	
	public function SetLan($Lan) { $this->Lan = $Lan; }
		/**	 * Outputs HTML paged data navigation.	 */
	public function PrintNav() {
		// prints paged navigation
		// if form uses POST to submit request then javascript is used to resubmit the form on every page (which is not perfect for usabilty)
		//		then to additional variables are needed: name of form and name of hidden field with page number
		// else GET is used then no js is necessary
		if ($this->FormMethod == 'GET') {
			$UseJs = false;	
		}
		else {
			// not implemented yet
			$FrmName = func_get_arg(0);			// name of form to submit with js
			$FldCurPageNum = func_get_arg(1);	// name of hidden input field with page number
			$UseJs = true;
		}		
		// language  dependend strings
		switch ($this->Lan) {
			case 'F':
				$LanStr01 = ' inscriptions';
				$LanStr02 = ' inscription';
				$LanStr03 = ' pages: ';
				$LanStr04 = ' page';
				$LanStr05 = 'Résultat de la recherche: ';
				$LanStr06 = '';
				break;
			case 'I':
				$LanStr01 = ' iscrizioni';
				$LanStr02 = ' inscriptione';
				$LanStr03 = ' pagine: ';
				$LanStr04 = ' pagina';
				$LanStr05 = 'Risultato della ricerca: ';
				$LanStr06 = '';
				break;				
			case 'E':
				$LanStr01 = ' entries';
				$LanStr02 = ' entry';
				$LanStr03 = ' pages: ';
				$LanStr04 = ' page';
				$LanStr05 = 'search result: ';
				$LanStr06 = 'on';
				break;
			default:
				$LanStr01 = ' Fotos';
				$LanStr02 = ' Foto';
				$LanStr03 = ' Seiten: ';
				$LanStr04 = ' pro Seite';
				$LanStr05 = ' sortiert nach: ';				
				break;
		}
				
		// calc total number of pages
		$NumPage = ceil($this->NumRec / $this->NumRecPerPage);
		// lower limit (start)
		$Start = 1;
		if ($this->CurPageNum - $this->Range/2 > 0) { $Start = $this->CurPageNum - $this->Range/2; }
		// upper limit (end)
		$End = $this->CurPageNum + $this->Range / 2;
		if ($this->CurPageNum + $this->Range/2 > $NumPage) { $End = $NumPage;	}
		// special cases
		if ($NumPage < $this->Range) { $End = $NumPage; }
		else if ($End < $this->Range) { $End = $this->Range; }

		echo '<div id="PagedNavBar" class="ToolbarTxt">';
		// to do: 
		// setup method POST
		// jump back big step
		if ($this->CurPageNum > $this->StepBig / 2) { // && $this->CurPageNum >= $this->StepBig + $this->StepSmall) {
			$StepBig = ($this->CurPageNum > $this->StepBig ? $this->StepBig : $this->CurPageNum - 1);
			if ($UseJs) { }
			else {
				echo '<a class="LinkJumpBig" href="'.$this->GetPage().$this->AddQuery(array($this->VarNamePgNav => ($this->CurPageNum - $StepBig))).'">';
				echo '<img src="layout/images/icon_backfast.gif" alt="Icon back" title="schnell Rückwärts blättern [-'.$StepBig.']"/></a>';
			}
		}		
		// jump back small step
		if ($this->CurPageNum > $this->StepSmall / 2) {
			$StepSmall = ($this->CurPageNum > $this->StepSmall ? $this->StepSmall : $this->CurPageNum - 1);
			if ($UseJs) { }
			else {
				echo '<a class="LinkJumpSmall" href="'.$this->GetPage().$this->AddQuery(array($this->VarNamePgNav => ($this->CurPageNum - $StepSmall))).'">';
				echo '<img src="layout/images/icon_back.gif" alt="Icon back" title="Rückwärts blättern [-'.$StepSmall.']"/></a>';
			}
		}		
		// direct accessible pages (1 2 3 4... links)
		$Count = 0;
		for ($i = $Start; $i <= $End; $i++) {
			if ($NumPage > 1) {
				if ($Count > 0) { echo ' '; }
				$Count++;
				if ($i == $this->CurPageNum) { echo ' <span class="LinkCurPageNum">'; }
				else {
					echo '<span class="Pages">';
					if ($UseJs) {	}
					else {
						echo '<a class="LinkJumpPage" href="'.$this->GetPage().$this->AddQuery(array($this->VarNamePgNav => $i)).'">';
					}
				}
				echo $i;	// page number
				if ($i == $this->CurPageNum) { echo '</span>'; }
				else { echo '</a></span>'; }
			}
		}		
		// jump forward small step
		if ($NumPage > $this->CurPageNum + $this->StepSmall / 2) {
			$StepSmall = ($NumPage > ($this->CurPageNum + $this->StepSmall) ? $this->StepSmall : $NumPage - $this->CurPageNum);
			if ($UseJs) { }
			else {
				echo '<a class="LinkJumpSmall" href="'.$this->GetPage().$this->AddQuery(array($this->VarNamePgNav => ($this->CurPageNum + $StepSmall))).'">';
				echo '<img src="layout/images/icon_forward.gif" alt="Icon forward" title="Vorwärts blättern [+'.$StepSmall.']"/></a>';
			}
		}
		// jump forward big step
		if ($NumPage >= $this->CurPageNum + $this->StepBig / 2) {
			$StepBig = ($NumPage > $this->CurPageNum + $this->StepBig ? $this->StepBig : $NumPage - $this->CurPageNum);
			if ($UseJs) { }
			else {
				echo '<a class="LinkJumpBig" href="'.$this->GetPage().$this->AddQuery(array($this->VarNamePgNav => ($this->CurPageNum + $StepBig))).'">';
				echo '<img src="layout/images/icon_forwardfast.gif" alt="Icon forward" title="schnell Vorwärts blättern [+'.$StepBig.']"/></a>';
			}
		}
		echo "</div>\n";
	}
}

?>