
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>VIEW RESULT</title>
</head>
<style>
	html{
        display: flex;
        flex-flow: row nowrap;
        justify-content: center;
        align-content: center;
        align-items: center;
        height:auto;
        margin: 0;
        padding: 0;
        background:#eeeeee;
    }

    body {


      }

@media print {
  body:before{
    zoom: 140%;
	content: 'RSS KADUNA';
	position: fixed;
	top: 0;
	bottom: 0;
	left: 0;
	right: 0;
	z-index: -1;

	color: #0d745e;
	font-size: 80px;
	font-weight: 500px;
	display: grid;
	justify-content: center;
	align-content: center;
	opacity: 0.2;
	transform: rotate(-45deg);
  }
 }

	table {
		  width: 87%;
		  word-break: break-all;


      }

	table,th, td {

     }

	th, td {
         border-color: #3359C2;

      }

	td{
		border-bottom: 1pt solid black;
	  }

	#tab{
		border-left-color: 1pt solid blue;
		border-top-left-radius: 5px;
		border-bottom-left-radius: 5px;
		border-top-right-radius: 5px;
		border-bottom-right-radius: 5px
	  }

	h5 {
		  font-size: 0.5em;
		}
	.logo{
		padding-left: 18px;
	}
	.logo2{
		padding-left: 2px;
		padding-right: none;
	}
	.img1{
		border-top-left-radius: 5px;
		border-bottom-left-radius: 5px;
		border-top-right-radius: 5px;
		border-bottom-right-radius: 5px
	}
	.img2{
		border-top-left-radius: 10px;
		border-bottom-left-radius: 10px;
		border-top-right-radius: 10px;
		border-bottom-right-radius: 10px
	}

	.grid-container-element {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-gap: 5px;

    width: 110%;
		}
	.grid-child-element {
			margin: 8px;

		}

		.grid-container-element2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-gap: 3px;

    width: 140%;
		}
	.grid-child-element2 {
			margin: 1px;

		}

	hr.solid {
		border-top: 6pt solid rgb(48, 17, 160);
		}


	@-webkit-keyframes fade-in {
	0% {
		opacity: 0;
	}
	40% {
		opacity: 0;
	}
	100% {
		opacity: 1;
	}
}

@-moz-keyframes reset {
	0% {
		opacity: 0;
	}
	100% {
		opacity: 0;
	}
}

@-moz-keyframes fade-in {
	0% {
		opacity: 0;
	}
	40% {
		opacity: 0;
	}
	100% {
		opacity: 1;
	}
}

@keyframes reset {
	0% {
		opacity: 0;
	}
	100% {
		opacity: 0;
	}
}

@keyframes fade-in {
	0% {
		opacity: 0;
	}
	40% {
		opacity: 0;
	}
	100% {
		opacity: 1;
	}
}

	.instaFade {
    -webkit-animation-name: reset, fade-in;
    -webkit-animation-duration: 1.5s;
    -webkit-animation-timing-function: ease-in;

	-moz-animation-name: reset, fade-in;
    -moz-animation-duration: 1.5s;
    -moz-animation-timing-function: ease-in;

	animation-name: reset, fade-in;
    animation-duration: 1.5s;
    animation-timing-function: ease-in;
}

.quickFade {
    -webkit-animation-name: reset, fade-in;
    -webkit-animation-duration: 2.5s;
    -webkit-animation-timing-function: ease-in;

	-moz-animation-name: reset, fade-in;
    -moz-animation-duration: 2.5s;
    -moz-animation-timing-function: ease-in;

	animation-name: reset, fade-in;
    animation-duration: 2.5s;
    animation-timing-function: ease-in;
}

.delayOne {
	-webkit-animation-delay: 0, .5s;
	-moz-animation-delay: 0, .5s;
	animation-delay: 0, .5s;
}

.delayTwo {
	-webkit-animation-delay: 0, 1s;
	-moz-animation-delay: 0, 1s;
	animation-delay: 0, 1s;
}

.delayThree {
	-webkit-animation-delay: 0, 1.5s;
	-moz-animation-delay: 0, 1.5s;
	animation-delay: 0, 1.5s;
}

.delayFour {
	-webkit-animation-delay: 0, 2s;
	-moz-animation-delay: 0, 2s;
	animation-delay: 0, 2s;
}

.delayFive {
	-webkit-animation-delay: 0, 2.5s;
	-moz-animation-delay: 0, 2.5s;
	animation-delay: 0, 2.5s;
}

</style>
<body class="quickFade delayFive">
<page size="A4">
<table id="tab" width="89%" height="693" border="0" cellspacing="0" frame="BOX" rule="NONE">
  <tbody border="1">
    <tr>
      <td width="135" height="128"><img src="{{ asset('schoolreport/rsslogo.jpeg') }}" width="135" height="119" alt="" class="img1"/></td>
      <td height="128" colspan="10"><img src="{{ asset('schoolreport/logo.jpeg') }}" width="485" height="125" alt="" class="logo1 img1"/></td>
        <?php
            foreach($broadsheets as $broadsheet){
                $broadsheet->picture;
            }
        if ($broadsheet->picture == NULL || $broadsheet->picture =="" || !isset($broadsheet->picture) ){

              // $cimage =  $imageclass;
               $image =  'unnamed.png';
        }else {

           $image =  $broadsheet->picture;
        }
        ?>

          <td height="128" colspan="4">
          <img src="{{ asset('images/studentpics/'.$image) }}" width="135" height="127" alt="" class=" logo2 img2"/>

     </td>
      </tr>
    <tr>
      <td height="119" colspan="14" >
		 <div class="grid-container-element">
			<div class="grid-child-element purple">
			  <table width="200" border="0">
			    <tbody>
			      <tr>
			        <td width="37%"  style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			               STUDENT'S NAME: </strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="63%"  style="font-size: 0.7em; border-bottom:none;"><strong> {{ strtoupper($broadsheet->tittle) }} &nbsp; {{ strtoupper($broadsheet->firstname) }} &nbsp; &nbsp; {{ strtoupper($broadsheet->lastname) }}</strong></td>
		          </tr>
			      <tr>
			        <td  style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			               ADMISSION NO:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			         <td width="63%"  style="font-size: 0.7em; border-bottom:none"><strong>{{ strtoupper($broadsheet->admissionno) }}</strong></td>
		          </tr>
					<tr>
			        <td  style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			                GENDER:</strong>
		                  </div>
		                </div>
		              </div>
			          </div></td>
			        <td width="63%"  style="font-size: 0.7em; border-bottom:none"><strong>{{ strtoupper($broadsheet->gender) }}</strong></td>
		          </tr><tr>
			        <td  style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			                HOUSE:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="63%"  style="font-size: 0.8em; border-bottom:none;"><strong>{{ strtoupper($broadsheet->house) }}</strong></td>
		          </tr><tr>
			        <td  style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.8em" align="right"><strong>
			                AGE:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="63%"  style="font-size: 0.8em; border-bottom:none;"><strong>
                       <?php
                       $age = Str::substr($broadsheet->dateofbirth, 0, 4);
                       $convertage = intval($age);
                       $nage = date("Y") - $convertage;


                      echo strtoupper($nage);
                       ?>
                    </strong></td>
		          </tr><tr>
			        <td  style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			                POSITION IN CLASS:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
                    <?php
                    foreach ($classposition as $key => $value) {

                    }


                    ?>
			        <td width="63%"  style="font-size: 0.8em; border-bottom:none"><strong>{{ $value->position }}</strong></td>
		          </tr>
		        </tbody>
		      </table>
              </div>
			<div class="grid-child-element green">
			<table width="200" border="0">
			    <tbody>
			      <tr>
			        <td width="52%"  style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			                 CLASS:</strong>

		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="48%"  style="font-size: 0.7em; border-bottom:none "><strong>{{ strtoupper($broadsheet->schoolclass) }} {{ strtoupper($broadsheet->arm) }}</strong></td>
		          </tr>
			      <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			                NO IN CLASS:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="48%"  style="font-size: 0.7em;  border-bottom:none"><strong>{{ $noofstudents }}</strong></td>
		          </tr>
                  @if ($chkclasssetting)
                  <?php
                  foreach ($classsetting as $key => $cl) {

                        }
                    ?>
                   <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			                NO. OF TIME SCH. OPENED:</strong>
		                  </div>
		                </div>
		              </div>
			          </div></td>
			       <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ $cl->noschoolopened }} </strong></td>
		           </tr>
                  @elseif(!$chkclasssetting)

                   <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			                NO. OF TIME SCH. OPENED:</strong>
		                  </div>
		                </div>
		              </div>
			          </div></td>
			       <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong>No Record</strong></td>
		           </tr>
                  @endif
                  <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			                ATTENDANCE FOR THE TERM:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
                    <?php
                    foreach ($studentprofile as $key => $v) {

                    }
			        ?>
                    <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong>{{ $v->attendance }}</strong></td>
		          </tr>
                  @if ($chkclasssetting)
                  <?php
                  foreach ($classsetting as $key => $cl) {

                        }
                    ?>
                  <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			                TERM ENDING:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="48%"  style="font-size: 0.7em;  border-bottom:none"><strong>{{ date( 'd F, Y',strtotime($cl->termends))}}</strong></td>
		          </tr>

                  @elseif (!$chkclasssetting)

                  <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"><strong>
			                TERM ENDING:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="48%"  style="font-size: 0.7em;  border-bottom:none"><strong>No Record</strong></td>
		          </tr>
                  @endif

                  @if ($chkclasssetting)
                  <?php
                  foreach ($classsetting as $key => $cl) {

                        }
                    ?>
					<tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.8em" align="right"><strong>
			                NEXT TERM BEGINS:</strong>
		                  </div>
		                </div>
		              </div>
			          </div></td>
			      <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong>{{ date( 'd F, Y',strtotime($cl->nexttermbegins)) }}</strong></td>
		          </tr>
                  @elseif (!$chkclasssetting)

					<tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.8em" align="right"><strong>
			                NEXT TERM BEGINS:</strong>
		                  </div>
		                </div>
		              </div>
			          </div></td>
			      <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong>No Record </strong></td>
		          </tr>
                  @endif
		        </tbody>
		      </table>
		 </div>

	 </div>

		</td>
    </tr>
    <tr >
      <td height="18" colspan="14" align="center" style="color: rgb(242, 37, 19);  "><strong>TERMINAL REPORT SHEET FOR FIRST TERM 2021/2022 SESSION&nbsp;
		</strong>

		</td>

    </tr>

	  <tr>

					<td  height="26" align="center" colspan="3" style="color: mediumblue;font-size: 0.9em">
						<strong >SUBJECTS</strong></td>
					<td width="48" align="center" style="color: mediumblue;font-size: 0.8em"><strong>CA1</strong></td>
					<td width="31" align="center" style="color: mediumblue;font-size: 0.8em"><strong>CA2</strong></td>
					<td width="52" align="center" style="color: mediumblue;font-size: 0.8em"><strong>EXAM</strong></td>
					<td width="52" align="center" style="color: mediumblue;font-size: 0.8em"><strong>TOTAL</strong></td>
					<td width="58" align="center" style="color: mediumblue;font-size: 0.8em"><strong>GRADE</strong></td>
                    <td width="32" align="center" style="color: mediumblue;font-size: 0.8em"><strong>SPC</strong></td>
					<td width="80" align="center" style="color: mediumblue;font-size: 0.8em"><strong>REMARK</strong></td>
					<td width="43" align="center" style="color: mediumblue;font-size: 0.8em"><strong>CMAX.</strong></td>
					<td width="43" align="center" style="color: mediumblue;font-size: 0.8em"><strong>CMIN.</strong></td>
					<td width="32" align="center" style="color: mediumblue;font-size: 0.8em"><strong>AVG</strong></td>

      				<td width="80" align="center" style="color: mediumblue;font-size: 0.8em"><strong>TEACHER</strong></td>

    </tr>
    <?php
    $color="";

    ?>
     @foreach ($broadsheets as $b )
    <tr id="tr1">

      <td width="25" align="justify" colspan="3" style="font-size: 0.8em">&nbsp; <strong>{{ $b->subject }}</strong></td>
      <td width="48" align="center" style="font-size: 0.9em"><strong>{{ $b->ca1 }}</strong></td>
      <td width="31" align="center" style="font-size: 0.9em"><strong>{{ $b->ca2 }}</strong></td>
      <td width="48" align="center" style="font-size: 0.9em "> <strong> {{ $b->exam }}  </strong></td>

      <td width="48" align="center" style="font-size: 0.9em ">
        @if ($b->total <= 29)
        <strong style="color: red">
       {{ $b->total }}
       </strong>
       @else
       <strong>
         {{ $b->total }}
         </strong>
       @endif
    </td>
      <td width="58" align="center" style="font-size: 0.9em">
    @if ($b->grade == "F")
    <strong style="color: red"> {{ $b->grade }}</strong>
    @else
    <strong>
        {{ $b->grade }}
        </strong>
    @endif
    </strong></td>
      <td width="32" align="center" style="font-size: 0.9em"><strong>{{ $b->position }}</strong></td>
      <td width="80" align="center" style="font-size: 0.7em"><strong>{{ $b->remark }}</strong></td>
      <td width="50" align="center" style="font-size: 0.9em"><strong>{{ $b->cmax }}</strong></td>
      <td width="50" align="center" style="font-size: 0.9em"><strong>{{ $b->cmin }}</strong></td>
      <td width="32" align="center" style="font-size: 0.9em"><strong>{{ $b->avg }}</strong></td>

      <td width="69" align="center" style="font-size: 0.9em"><strong>
        <?php
            $name = explode(' ',trim($b->staffname) );
            echo substr(Str::ucfirst($name[0]),0,1).".". " ".Str::ucfirst($name[1]);
            ?>
    </strong></td>
    </tr>
    @endforeach
    <tr>
      <td height="158" colspan="14">
		<hr>
		<div class="grid-container-element">
			<div class="grid-child-element purple">

			  <table width="200" border="0">
			    <tbody>
			      <tr>
			        <td width="52%" style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right">
			              <strong> PUNCTUALITY:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ strtoupper($b->punctuality) }}</strong></td>
		          </tr>
			      <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right">
							<strong> NEATNESS IN APPEARANCE  :</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			         <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ strtoupper($b->neatness) }}</strong></td>
		          </tr>
					<tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"> <strong>LEADERSHIP SKILLS:</strong>
		                  </div>
		                </div>
		              </div>
			          </div></td>
			        <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ strtoupper($b->leadership) }}</strong></td>
		          </tr><tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"> <strong>ATTITUDE TO SCHOOL WORK:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ strtoupper($b->attitude) }}</strong></td>
		          </tr><tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"> <strong>READING SKILLS:</strong> </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ strtoupper($b->reading) }}</strong></td>
		          </tr><tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right"> <strong>HONESTY:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="48%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ strtoupper($b->honesty) }}</strong></td>
		          </tr>
		        </tbody>
		      </table>

              </div>

				<div class="grid-child-element green">
			<table width="200" border="0">
			    <tbody>
			      <tr>
			        <td width="51%" style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em; border-bottom:none" align="right"> <strong>COOPERATION WITH OTHERS:</strong>

		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="49%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ strtoupper($b->cooperation) }}</strong></td>
		          </tr>
			      <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em; border-bottom:none" align="right">
							<strong>SELF CONTROL:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="49%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ strtoupper($b->selfcontrol) }}</strong></td>
		          </tr>
					<tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em; border-bottom:none" align="right">
			                <strong> POLITENESS:</strong>
		                  </div>
		                </div>
		              </div>
			          </div></td>
			       <td width="49%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ strtoupper($b->politeness) }}</strong></td>
		          </tr><tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em; border-bottom:none" align="right">
							<strong>  PHYSICAL HEALTH:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="49%"  style="font-size: 0.7em; border-bottom:none"><strong> {{ strtoupper($b->physicalhealth) }}</strong></td>
		          </tr><tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em; border-bottom:none" align="right">
							<strong>EMOTIONAL STABILITY:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="49%"  style="font-size: 0.7em; border-bottom:none"><strong>{{ strtoupper($b->stability) }}</strong></td>
		          </tr>
					<tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em" align="right">
							<strong>  GAMES AND SPORTS:</strong>
		                  </div>
		                </div>
		              </div>
			          </div></td>
			      <td width="49%"  style="font-size: 0.7em; border-bottom:none"><strong>{{ strtoupper($b->gamesandsports) }}</strong></td>
		          </tr>
		        </tbody>
		      </table>

		 </div>

	 </div>

		</td>
    </tr>

    <tr>

      <td height="19" colspan="14">

		  <div class="grid-container-element">
			<div class="grid-child-element purple">

			  <table width="200" border="0">
			    <tbody>
			      <tr>
			        <td width="47%" style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.8em; border-bottom:none" align="right">TOTAL SCORE :
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="53%"  style="font-size: 0.8em;border-bottom:none"><strong>{{ $subjectstotal }}</strong></td>
		          </tr>
			      <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.8em" align="right">
			               AVERAGE SCORE:
		                  </div>
		                </div>
		              </div>
		            </div></td>
			         <td width="53%"  style="font-size: 0.8em; border-bottom:none"><strong>{{ round($subjectsavg,2) }}</strong></td>
		          </tr>
		        </tbody>
		      </table>

              </div>


	 </div>
		</td>
    </tr>
	<tr>
      <td height="15" colspan="14">

		  <div class="grid-container-element2">
			<div class="grid-child-element2 purple">

			  <table width="300" border="0">
			    <tbody>
			      <tr>
			        <td width="40%" style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.8em" align="left"><strong> TEACHER'S COMMENT:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="60%"  style="font-size: 0.7em; border-bottom:none">{{ strtoupper($b->classteachercomment) }}</td>
		          </tr>
				  <tr>
			        <td width="40%" style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
								<p></p>
		                </div>
		              </div>
		             </div>
				    </td>
			        <td width="60%"  style="font-size: 0.7em; border-bottom:none"><p></p></td>
		          </tr>

			      <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.8em" align="left">
			              <strong> PRINCIPAL'S COMMENT:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			         <td width="60%"  style="font-size: 0.7em;  border-bottom:none">{{ strtoupper($b->principalscomment) }}</td>
		          </tr>
		        </tbody>
		      </table>

            </div>

				<div class="grid-child-element2 green">
			<table width="200" border="0" ">
			    <tbody>
			      <tr>
			        <td width="30%" style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em; border-bottom:none" align="left"><strong>SIGNATURE:</strong>

		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="70%"  style="font-size: 0.7em; border-bottom:none">&nbsp;</td>
		          </tr>
				  <tr>
			        <td width="40%" style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
								<p></p>
		                </div>
		              </div>
		             </div>
				    </td>
			        <td width="60%"  style="font-size: 0.7em; border-bottom:none"><p></p></td>
		          </tr>
			      <tr>
			        <td style="border-bottom: none"><div title="Page 1">
			          <div>
			            <div>
			              <div  style="font-size: 0.7em; " align="left">
			               <strong> SIGNATURE:</strong>
		                  </div>
		                </div>
		              </div>
		            </div></td>
			        <td width="70%"  style="font-size: 0.7em;border-bottom:none" align="left">&nbsp;</td>
		          </tr>
		        </tbody>
		      </table>

		 </div>
          <strong style="font-size: 0.7em">Redeemer's Secondary School: Nurturing Godly Seeds		  </strong></div>


	  </td>
    </tr>
	<tr>

      <td height="19" colspan="14" style="border-bottom: none"> <p>&nbsp;</p></td>

    </tr>
  </tbody>
  </table>
</page>
	<button onclick="window.print()">Print this page</button>
</body>
</html>
