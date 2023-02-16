// @ts-check

class MyReporter {
  onBegin(config, suite) {
    console.log(`Starting the run with ${suite.allTests().length} tests`);
    console.log(`Suite Title: ${suite.suites[0].suites[0].suites[0].title} tests`);
    console.log("======================================");
  }

  onTestBegin(test) {
    console.log(`Test started: ${test.title}`);
  }

  onStepBegin(test, result, step) {
    if (step.category == "test.step") {
      console.log(`Test step : ${step.title}`);
    }
  }

  // onStepEnd(test, result, step) {
  //   if (step.category == "test.step") {
  //     console.log(`Test step end: ${step.title}`);
  //   }
  // }

  onStdOut(chunk, test, result) {
    console.log(chunk);
  }

  onTestEnd(test, result) {
    console.log(`Finished test ${test.title}: ${result.status}`);
    if (result.status === 'failed') {
      console.log(result.error?.message);
      console.log(result.error?.stack);
    }
    console.log("======================================");
  }

  onEnd(result) {
    console.log(`Finished the run: ${result.status}`);
  }

}

module.exports = MyReporter;