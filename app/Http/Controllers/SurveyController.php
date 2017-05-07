<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Surveys;
use App\Questions;
use Webpatser\Uuid\Uuid;

class SurveyController extends Controller {
  /**
   * Validate the survey.
   */
  public function validateSurvey(Request $request) {
    $this->validate($request, [
      'name' => 'required|max:127|min:3'
    ]);
  }

  /**
   * Show the survey creation page.
   *
   * @return \Illuminate\Http\Response
   */
  public function create() {
    return view('survey.create');
  }

  /**
   * Create a new survey.
   *
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request) {
    $this->validateSurvey($request);

    $survey = new Surveys;
    $survey->user_id = $request->user()->id;
    $survey->name = $request->input('name');
    $survey->uuid = Uuid::generate(4);
    $survey->description = $request->input('description');
    $survey->save();
    $request->session()->flash('success', 'Survey ' . $survey->uuid . ' successfully created!');
    return redirect()->route('survey.edit', $survey->uuid);
  }

  /**
   * Show survey editing page.
   *
   * @return \Illuminate\Http\Response
   */
  public function edit($uuid, Request $request) {
    $survey = Surveys::getByOwner($uuid, $request->user()->id);

    if(!$survey):
      $request->session()->flash('warning', 'Survey "' . $uuid . '" not found.');
      return redirect()->route('dashboard');
    endif;

    return view('survey.edit')->with([
      'survey' => $survey,
      'questions' => Questions::getAllByOwner($survey->id)
    ]);
  }

  /**
   * Update the survey.
   *
   * @return \Illuminate\Http\Response
   */
  public function update($uuid, Request $request) {
    $this->validateSurvey($request);

    $survey = Surveys::getByOwner($uuid, $request->user()->id);

    if(!$survey):
      $request->session()->flash('warning', 'Survey "' . $uuid . '" not found.');
      return redirect()->route('dashboard');
    endif;

    $survey->name = $request->input('name');
    $survey->description = $request->input('description');
    $survey->save();
    $request->session()->flash('success', 'Survey ' . $survey->uuid . ' successfully updated!');
    return redirect()->route('dashboard');
  }

  /**
   * Delete the survey.
   *
   * @return \Illuminate\Http\Response
   */
  public function destroy($uuid, Request $request) {
    $deleted = Surveys::deleteByOwner($uuid, $request->user()->id);

    if($deleted):
      $request->session()->flash('success', 'Survey "' . $uuid . '" successfully removed!');
    else:
      $request->session()->flash('warning', 'Survey "' . $uuid . '" not found.');
    endif;

    return redirect()->route('dashboard');
  }

  /**
   * Run the survey.
   *
   * @return \Illuminate\Http\Response
   */
  public function run($uuid, Request $request) {
    $status = Surveys::run($uuid, $request->user()->id);

    if($status === Surveys::ERR_RUN_SURVEY_NOT_FOUND):
      $request->session()->flash('warning', 'Survey "' . $uuid . '" not found.');
      return redirect()->route('dashboard');
    elseif($status === Surveys::ERR_RUN_SURVEY_INVALID_STATUS):
      $request->session()->flash('warning', 'Survey "' . $uuid . '" invalid status, it should be "draft".');
      return redirect()->route('survey.edit', $uuid);
    elseif($status === Surveys::ERR_RUN_SURVEY_ALREADY_RUNNING):
      $request->session()->flash('warning', 'Survey "' . $uuid . '" already running.');
      return redirect()->route('survey.edit', $uuid);
    endif;

    $request->session()->flash('success', 'Survey "' . $uuid . '" is now running.');
    return redirect()->route('survey.edit', $uuid);
  }

  /**
   * Pause the survey.
   *
   * @return \Illuminate\Http\Response
   */
  public function pause($uuid, Request $request) {
    $status = Surveys::pause($uuid, $request->user()->id);

    if($status === Surveys::ERR_PAUSE_SURVEY_NOT_FOUND):
      $request->session()->flash('warning', 'Survey "' . $uuid . '" not found.');
      return redirect()->route('dashboard');
    elseif($status === Surveys::ERR_PAUSE_SURVEY_INVALID_STATUS):
      $request->session()->flash('warning', 'Survey "' . $uuid . '" invalid status, it should be "ready".');
      return redirect()->route('survey.edit', $uuid);
    elseif($status === Surveys::ERR_PAUSE_SURVEY_ALREADY_PAUSED):
      $request->session()->flash('warning', 'Survey "' . $uuid . '" already paused.');
      return redirect()->route('survey.edit', $uuid);
    endif;

    $request->session()->flash('success', 'Survey "' . $uuid . '" is now paused.');
    return redirect()->route('survey.edit', $uuid);
  }
}

