import React from 'react';
import PropTypes from 'prop-types';
import { CSSTransitionGroup } from 'react-transition-group';
import Question from '../components/Question';
import Intro from '../components/Intro';
import AnswerOption from '../components/AnswerOption';

function Quiz(props) {
  function renderAnswerOptions(key, index) {
    return (
      <AnswerOption
        key={index}
        index={index}
        answerContent={key.name}
        questionId={props.questionId}
        image={key.image}
        onAnswerSelected={props.onAnswerSelected}
      />
    );
  }

  function renderIntro() {
    return (
      <Intro
        introIcon={props.introIcon}
        introTitle={props.introTitle}
        introImage={props.introImage}
        introDetails={props.introDetails}
        onAnswerSelected={props.onAnswerSelected}
      />
    );
  }

  function renderQuestion() {
    return (
      <>
        <Question question={props.question}/>
        <div className="quiz_slide__questions">
          {props.answerOptions.map(renderAnswerOptions)}
        </div>
      </>
  );
  }

  return (
    <CSSTransitionGroup
      className="container"
      component="div"
      transitionName="fade"
      transitionEnterTimeout={800}
      transitionLeaveTimeout={500}
      transitionAppear
      transitionAppearTimeout={500}
    >
      <div key={props.questionId}>
        <div className="quiz_slide quiz_slide--first">
          <div className="quiz_slide__inner">
            {props.questionId == 0 ? renderIntro() : renderQuestion()}
          </div>
        </div>
      </div>
    </CSSTransitionGroup>
  );
}

Quiz.propTypes = {
  givenAnswer: PropTypes.number,
  answerOptions: PropTypes.array,
  question: PropTypes.string,
  questionId: PropTypes.number.isRequired,
  onAnswerSelected: PropTypes.func.isRequired,
  introIcon: PropTypes.string.isRequired,
  introTitle: PropTypes.string.isRequired,
  introImage: PropTypes.object.isRequired,
  introDetails: PropTypes.string.isRequired,
};

export default Quiz;
