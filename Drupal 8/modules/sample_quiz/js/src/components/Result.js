import React from 'react';
import PropTypes from 'prop-types';
import { CSSTransitionGroup } from 'react-transition-group';

function renderReset(props) {
  return (
    <>
      <div className="reset_btn" >
        <button onClick={props.onReset}>Reset!</button>
      </div>
    </>
  );
}

function renderResult(props) {
  return (
    <>
      <strong>{props.quizResults}</strong>
    </>
  );
}

function Result(props) {
  return (
    <CSSTransitionGroup
      className="container result"
      component="div"
      transitionName="fade"
      transitionEnterTimeout={800}
      transitionLeaveTimeout={500}
      transitionAppear
      transitionAppearTimeout={500}
    >
      <div className="quiz_slide quiz_slide--last">
        <div className="quiz_slide__inner">
          <div className="quiz_slide__teaser">
            <span aria-hidden="true" className={props.resultIcon}></span>
            <h2>{props.resultTitle}</h2>
          </div>
          <div className="quiz_slide__content">
            <div className="content">
              <div className="content__inner">
                <div dangerouslySetInnerHTML={{__html: props.resultText }}></div>
                {props.quizType !== 'tag' ? renderResult(props) : ''}
              </div>
              {props.quizType === 'tag' ? renderReset(props) : ''}
            </div>
          </div>
        </div>
      </div>
    </CSSTransitionGroup>
  );
}

Result.propTypes = {
  resultIcon: PropTypes.string.isRequired,
  resultTitle: PropTypes.string.isRequired,
  resultText: PropTypes.string.isRequired,
  quizResults: PropTypes.object.isRequired,
  quizType: PropTypes.string.isRequired,
  onReset: PropTypes.func.isRequired,
};

export default Result;
